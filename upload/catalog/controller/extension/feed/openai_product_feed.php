<?php
class ControllerExtensionFeedOpenaiProductFeed extends Controller {
	private const DEFAULT_BATCH_SIZE = 500;
	private const MAX_BATCH_SIZE = 2000;

	public function index() {
		if (function_exists('set_time_limit')) {
			@set_time_limit(0);
		}

		$this->load->model('extension/feed/openai_product_feed');

		$batch_size = $this->getBoundedInt('batch', self::DEFAULT_BATCH_SIZE, 100, self::MAX_BATCH_SIZE);
		$offset = $this->getBoundedInt('offset', 0, 0, PHP_INT_MAX);
		$limit = isset($this->request->get['limit']) ? $this->getBoundedInt('limit', 0, 1, PHP_INT_MAX) : 0;
		$currency = $this->getFeedCurrencyCode();
		$output = '';
		$written = 0;

		do {
			$remaining = $limit ? ($limit - $written) : $batch_size;
			$current_limit = $limit ? min($batch_size, $remaining) : $batch_size;

			if ($current_limit <= 0) {
				break;
			}

			$products = $this->model_extension_feed_openai_product_feed->getProducts($offset, $current_limit);

			if (!$products) {
				break;
			}

			foreach ($products as $product) {
				$record = $this->buildRecord($product, $currency);

				if (!$record) {
					continue;
				}

				$json = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

				if ($json !== false) {
					$output .= $json . "\n";
					$written++;
				}
			}

			$offset += count($products);

			if (count($products) < $current_limit) {
				break;
			}
		} while (!$limit || $written < $limit);

		$this->response->addHeader('Content-Type: application/x-ndjson; charset=utf-8');
		$this->response->addHeader('Cache-Control: public, max-age=900');
		$this->response->addHeader('X-Robots-Tag: noindex');
		$this->response->addHeader('Content-Disposition: inline; filename="openai-products.jsonl"');
		$this->response->setOutput($output);
	}

	private function buildRecord(array $product, string $currency): array {
		$title = $this->truncateText($this->cleanText($product['name'] ?? ''), 150);
		$description = $this->truncateText($this->cleanText(($product['description'] ?? '') ?: ($product['meta_description'] ?? '') ?: $title), 5000);
		$image_url = $this->getImageUrl($product['image'] ?? '');
		$regular_price = $this->getPriceValue($product['price'] ?? 0, $product['tax_class_id'] ?? 0, $currency);

		if ($title === '' || $description === '' || $image_url === '' || $regular_price <= 0) {
			return array();
		}

		$url = $this->normalizeUrl($this->url->link('product/product', 'product_id=' . (int)$product['product_id'], true));
		$brand = $this->truncateText($this->cleanText(($product['manufacturer'] ?? '') ?: ($this->config->get('config_name') ?: 'Chipoteka')), 70);
		$item_id = (string)(int)$product['product_id'];
		$record = array(
			'is_eligible_search' => 'true',
			'is_eligible_checkout' => 'false',
			'item_id' => $item_id,
			'title' => $title,
			'description' => $description,
			'url' => $url,
			'brand' => $brand,
			'condition' => 'new',
			'image_url' => $image_url,
			'price' => $this->formatPrice($regular_price, $currency),
			'availability' => $this->getAvailability($product),
			'seller_name' => $this->truncateText($this->cleanText($this->config->get('config_name') ?: 'Chipoteka'), 70),
			'seller_url' => $this->getStoreBaseUrl(),
			'seller_privacy_policy' => $this->url->link('information/information', 'information_id=6', true),
			'seller_tos' => $this->url->link('information/information', 'information_id=5', true),
			'accepts_returns' => 'true',
			'return_deadline_in_days' => 14,
			'return_policy' => $this->url->link('information/information', 'information_id=3', true),
			'target_countries' => array('HR'),
			'store_country' => 'HR',
			'is_digital' => 'false',
			'offer_id' => $item_id
		);

		$gtin = $this->getGtin($product);

		if ($gtin !== '') {
			$record['gtin'] = $gtin;
		}

		$mpn = $this->getMpn($product);

		if ($mpn !== '') {
			$record['mpn'] = $mpn;
		}

		if (!empty($product['product_category'])) {
			$record['product_category'] = $this->cleanText($product['product_category']);
		}

		$additional_image_urls = $this->getAdditionalImageUrls($product, $image_url);

		if ($additional_image_urls) {
			$record['additional_image_urls'] = implode(',', $additional_image_urls);
		}

		$sale_price = $this->getSalePriceValue($product, $currency);

		if ($sale_price > 0 && $sale_price <= $regular_price) {
			$record['sale_price'] = $this->formatPrice($sale_price, $currency);
		}

		if (!empty($product['price_last_30']) && (float)$product['price_last_30'] > 0) {
			$last_30_price = $this->getPriceValue($product['price_last_30'], $product['tax_class_id'] ?? 0, $currency);
			$record['pricing_trend'] = 'Lowest price in last 30 days: ' . $this->formatPrice($last_30_price, $currency);
		}

		if (!empty($product['weight']) && (float)$product['weight'] > 0 && !empty($product['weight_class'])) {
			$record['weight'] = $this->trimDecimal($product['weight']);
			$record['item_weight_unit'] = strtolower($this->cleanText($product['weight_class']));
		}

		if ((float)($product['length'] ?? 0) > 0 && (float)($product['width'] ?? 0) > 0 && (float)($product['height'] ?? 0) > 0 && !empty($product['length_class'])) {
			$unit = strtolower($this->cleanText($product['length_class']));
			$record['dimensions'] = $this->trimDecimal($product['length']) . 'x' . $this->trimDecimal($product['width']) . 'x' . $this->trimDecimal($product['height']) . ' ' . $unit;
		}

		if (!empty($product['reviews']) && (int)$product['reviews'] > 0 && !empty($product['rating']) && (float)$product['rating'] > 0) {
			$record['review_count'] = (int)$product['reviews'];
			$record['star_rating'] = number_format((float)$product['rating'], 2, '.', '');
		}

		$shipping_price = ($sale_price > 0 ? $sale_price : $regular_price) >= 66.36 ? 0.0 : 5.17;
		$record['shipping'] = 'HR::Standard:' . $this->formatPrice($shipping_price, $currency);

		return $record;
	}

	private function getBoundedInt(string $key, int $default, int $min, int $max): int {
		$value = isset($this->request->get[$key]) ? (int)$this->request->get[$key] : $default;

		if ($value < $min) {
			return $min;
		}

		if ($value > $max) {
			return $max;
		}

		return $value;
	}

	private function getFeedCurrencyCode(): string {
		if ($this->currency->has('EUR')) {
			return 'EUR';
		}

		return $this->config->get('config_currency') ?: 'EUR';
	}

	private function getPriceValue($price, $tax_class_id, string $currency): float {
		$taxed = $this->tax->calculate((float)$price, (int)$tax_class_id, $this->config->get('config_tax'));

		return (float)$this->currency->format($taxed, $currency, '', false);
	}

	private function getSalePriceValue(array $product, string $currency): float {
		if (!isset($product['special']) || $product['special'] === '' || (float)$product['special'] <= 0) {
			return 0.0;
		}

		return $this->getPriceValue($product['special'], $product['tax_class_id'] ?? 0, $currency);
	}

	private function formatPrice(float $price, string $currency): string {
		return number_format($price, 2, '.', '') . ' ' . $currency;
	}

	private function getAvailability(array $product): string {
		$quantity = max((int)($product['quantity'] ?? 0), 0);
		$quantity_ws = max((int)($product['quantityws'] ?? 0), 0);

		if ($quantity > 0 || $quantity_ws > 0) {
			return 'in_stock';
		}

		$stock_status = $this->normalizeText($product['stock_status'] ?? '');

		if (strpos($stock_status, 'prednar') !== false || strpos($stock_status, 'u dolasku') !== false || strpos($stock_status, 'uskoro') !== false) {
			return 'pre_order';
		}

		if (
			strpos($stock_status, 'dobavlj') !== false ||
			strpos($stock_status, 'naruc') !== false ||
			strpos($stock_status, 'narud') !== false ||
			strpos($stock_status, 'izdvojen') !== false
		) {
			return 'backorder';
		}

		return 'out_of_stock';
	}

	private function getGtin(array $product): string {
		foreach (array($product['upc'] ?? '', $product['ean'] ?? '') as $identifier) {
			$gtin = preg_replace('/\D+/', '', (string)$identifier);

			if (in_array(strlen($gtin), array(8, 12, 13, 14), true)) {
				return $gtin;
			}
		}

		return '';
	}

	private function getMpn(array $product): string {
		foreach (array($product['mpn'] ?? '', $product['model'] ?? '') as $candidate) {
			$identifier = $this->truncateText($this->cleanText($candidate), 70);

			if ($this->isUsableManufacturerIdentifier($identifier)) {
				return $identifier;
			}
		}

		return '';
	}

	private function isUsableManufacturerIdentifier(string $identifier): bool {
		if ($identifier === '' || $identifier === '-' || $identifier === '0') {
			return false;
		}

		if (strlen($identifier) < 3) {
			return false;
		}

		$normalized = $this->normalizeText($identifier);

		if (preg_match('/^\d+\s*(godin|mjesec|mj|year|years|month|months)/', $normalized)) {
			return false;
		}

		if (preg_match('/\b(garanc|jamstvo|warranty|dobavlj|naruc|narud|isporuk)\b/', $normalized)) {
			return false;
		}

		return (bool)preg_match('/[a-z0-9]/i', $identifier);
	}

	private function getAdditionalImageUrls(array $product, string $primary_image_url): array {
		$urls = array();

		foreach (($product['additional_images'] ?? array()) as $image) {
			$url = $this->getImageUrl($image);

			if ($url !== '' && $url !== $primary_image_url) {
				$urls[] = $url;
			}
		}

		return array_values(array_unique($urls));
	}

	private function getImageUrl($image): string {
		$image = trim((string)$image);

		if ($image === '') {
			return '';
		}

		if (preg_match('#^https?://#i', $image)) {
			return str_replace(' ', '%20', $image);
		}

		$path = ltrim($image, '/');

		if (strpos($path, 'image/') !== 0) {
			$path = 'image/' . $path;
		}

		return str_replace(' ', '%20', $this->getStoreBaseUrl() . '/' . $path);
	}

	private function normalizeUrl($url): string {
		$url = html_entity_decode(trim((string)$url), ENT_QUOTES, 'UTF-8');

		if ($url === '') {
			return $this->getStoreBaseUrl();
		}

		if (preg_match('#^https?://#i', $url)) {
			return str_replace(' ', '%20', $url);
		}

		return str_replace(' ', '%20', $this->getStoreBaseUrl() . '/' . ltrim($url, '/'));
	}

	private function getStoreBaseUrl(): string {
		return rtrim($this->config->get('config_ssl') ?: $this->config->get('config_url'), '/');
	}

	private function cleanText($text): string {
		$text = html_entity_decode(strip_tags((string)$text), ENT_QUOTES, 'UTF-8');
		$clean = preg_replace('/\s+/u', ' ', $text);

		if ($clean !== null) {
			$text = $clean;
		}

		if (function_exists('iconv')) {
			$converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text);

			if ($converted !== false) {
				$text = $converted;
			}
		}

		return trim($text);
	}

	private function normalizeText($text): string {
		$text = $this->cleanText($text);

		if (function_exists('iconv')) {
			$transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

			if ($transliterated !== false) {
				$text = $transliterated;
			}
		}

		return strtolower($text);
	}

	private function truncateText(string $text, int $limit): string {
		if ($text === '' || $limit <= 0) {
			return '';
		}

		if (function_exists('mb_strlen') && function_exists('mb_substr')) {
			if (mb_strlen($text, 'UTF-8') <= $limit) {
				return $text;
			}

			return rtrim(mb_substr($text, 0, $limit - 3, 'UTF-8')) . '...';
		}

		if (strlen($text) <= $limit) {
			return $text;
		}

		return rtrim(substr($text, 0, $limit - 3)) . '...';
	}

	private function trimDecimal($value): string {
		return rtrim(rtrim(number_format((float)$value, 4, '.', ''), '0'), '.');
	}
}
