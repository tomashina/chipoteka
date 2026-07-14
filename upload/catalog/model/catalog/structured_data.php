<?php
class ModelCatalogStructuredData extends Model {
	public function buildCategoryCollection(array $category_info, array $breadcrumbs, array $products, string $canonical_url, int $total_products, int $offset = 0): string {
		$canonical_url = $this->normalizeUrl($canonical_url);
		$page_node_id = $canonical_url . '#webpage';
		$list_node_id = $canonical_url . '#itemlist';
		$category_node_id = $canonical_url . '#category';

		$category_node = array_filter(array(
			'@type' => 'Thing',
			'@id' => $category_node_id,
			'name' => $this->cleanText($category_info['name'] ?? ''),
			'description' => $this->truncateText($this->cleanText($category_info['description'] ?? $category_info['meta_description'] ?? ''), 800),
			'image' => $this->getImageUrl($category_info['image'] ?? '')
		));

		$graph = array(
			array_filter(array(
				'@type' => 'CollectionPage',
				'@id' => $page_node_id,
				'url' => $canonical_url,
				'name' => $this->cleanText($category_info['name'] ?? ''),
				'description' => $this->truncateText($this->cleanText($category_info['meta_description'] ?? $category_info['description'] ?? ''), 800),
				'image' => $this->getImageUrl($category_info['image'] ?? ''),
				'about' => array('@id' => $category_node_id),
				'mainEntity' => array('@id' => $list_node_id)
			)),
			$category_node,
			$this->buildItemList($list_node_id, $products, $total_products, $offset),
			$this->buildBreadcrumbList($canonical_url, $breadcrumbs)
		);

		return $this->encodeGraph($graph);
	}

	public function buildBrandCollection(array $manufacturer_info, array $breadcrumbs, array $products, string $canonical_url, int $total_products, int $offset = 0): string {
		$canonical_url = $this->normalizeUrl($canonical_url);
		$page_node_id = $canonical_url . '#webpage';
		$list_node_id = $canonical_url . '#itemlist';
		$brand_node_id = $canonical_url . '#brand';

		$brand_node = array_filter(array(
			'@type' => 'Brand',
			'@id' => $brand_node_id,
			'name' => $this->cleanText($manufacturer_info['name'] ?? ''),
			'url' => $canonical_url,
			'description' => $this->truncateText($this->cleanText($manufacturer_info['description'] ?? $manufacturer_info['meta_description'] ?? ''), 800),
			'logo' => $this->getImageUrl($manufacturer_info['image'] ?? ''),
			'image' => $this->getImageUrl($manufacturer_info['image'] ?? '')
		));

		$graph = array(
			array_filter(array(
				'@type' => 'CollectionPage',
				'@id' => $page_node_id,
				'url' => $canonical_url,
				'name' => $this->cleanText($manufacturer_info['name'] ?? ''),
				'description' => $this->truncateText($this->cleanText($manufacturer_info['meta_description'] ?? $manufacturer_info['description'] ?? ''), 800),
				'image' => $this->getImageUrl($manufacturer_info['image'] ?? ''),
				'about' => array('@id' => $brand_node_id),
				'mainEntity' => array('@id' => $list_node_id)
			)),
			$brand_node,
			$this->buildItemList($list_node_id, $products, $total_products, $offset),
			$this->buildBreadcrumbList($canonical_url, $breadcrumbs)
		);

		return $this->encodeGraph($graph);
	}

	private function buildItemList(string $list_node_id, array $products, int $total_products, int $offset): array {
		$items = array();
		$position = max($offset, 0) + 1;

		foreach ($products as $product) {
			$product_node = $this->buildProductListItem($product);

			if (!$product_node) {
				continue;
			}

			$items[] = array(
				'@type' => 'ListItem',
				'position' => $position++,
				'item' => $product_node
			);
		}

		return array(
			'@type' => 'ItemList',
			'@id' => $list_node_id,
			'numberOfItems' => $total_products,
			'itemListElement' => $items
		);
	}

	private function buildProductListItem(array $product): array {
		if (empty($product['product_id']) || empty($product['name'])) {
			return array();
		}

		$url = $this->normalizeUrl($this->url->link('product/product', 'product_id=' . (int)$product['product_id'], true));
		$product_node = array_filter(array(
			'@type' => 'Product',
			'@id' => $url . '#product',
			'name' => $this->cleanText($product['name'] ?? ''),
			'url' => $url,
			'image' => $this->getImageUrl($product['image'] ?? ''),
			'sku' => $this->cleanText(($product['sku'] ?? '') ?: ($product['model'] ?? '')),
			'model' => $this->cleanText($product['model'] ?? '')
		));

		if (!empty($product['manufacturer'])) {
			$product_node['brand'] = array(
				'@type' => 'Brand',
				'name' => $this->cleanText($product['manufacturer'])
			);
		}

		$this->addProductIdentifiers($product_node, $product);

		$product_node['offers'] = array(
			'@type' => 'Offer',
			'url' => $url,
			'priceCurrency' => $this->getCurrencyCode(),
			'price' => number_format($this->getOfferPrice($product), 2, '.', ''),
			'availability' => $this->getAvailability($product),
			'itemCondition' => 'https://schema.org/NewCondition'
		);

		return $product_node;
	}

	private function buildBreadcrumbList(string $canonical_url, array $breadcrumbs): array {
		$items = array();
		$position = 1;
		$last_index = count($breadcrumbs) - 1;

		foreach ($breadcrumbs as $index => $breadcrumb) {
			$name = $this->cleanText($breadcrumb['text'] ?? '');

			if ($name === '') {
				continue;
			}

			$items[] = array(
				'@type' => 'ListItem',
				'position' => $position++,
				'name' => $name,
				'item' => ($index === $last_index) ? $canonical_url : $this->normalizeUrl($breadcrumb['href'] ?? '')
			);
		}

		return array(
			'@type' => 'BreadcrumbList',
			'@id' => $canonical_url . '#breadcrumb',
			'itemListElement' => $items
		);
	}

	private function addProductIdentifiers(array &$product_node, array $product): void {
		$this->addGtin($product_node, $product['upc'] ?? '');
		$ean = trim((string)($product['ean'] ?? ''));

		if ($ean !== '' && !$this->addGtin($product_node, $ean)) {
			$product_node['mpn'] = $this->cleanText($ean);
		}
	}

	private function addGtin(array &$product_node, $identifier): bool {
		$gtin = preg_replace('/\D+/', '', (string)$identifier);

		if ($gtin === '') {
			return false;
		}

		$property = 'gtin';

		if (in_array(strlen($gtin), array(8, 12, 13, 14), true)) {
			$property = 'gtin' . strlen($gtin);
		}

		if (!isset($product_node[$property])) {
			$product_node[$property] = $gtin;
			return true;
		}

		return false;
	}

	private function getOfferPrice(array $product): float {
		$price = (isset($product['special']) && $product['special'] !== '' && (float)$product['special'] >= 0)
			? (float)$product['special']
			: (float)($product['price'] ?? 0);

		return (float)$this->tax->calculate(
			$price,
			$product['tax_class_id'] ?? 0,
			$this->config->get('config_tax')
		);
	}

	private function getAvailability(array $product): string {
		$quantity = max((int)($product['quantity'] ?? 0), 0);
		$quantity_ws = max((int)($product['quantityws'] ?? 0), 0);

		if ($quantity > 0 || $quantity_ws > 0) {
			return 'https://schema.org/InStock';
		}

		$stock_status = $this->normalizeText($product['stock_status'] ?? '');

		if (strpos($stock_status, 'prednar') !== false || strpos($stock_status, 'u dolasku') !== false || strpos($stock_status, 'uskoro') !== false) {
			return 'https://schema.org/PreOrder';
		}

		if (
			strpos($stock_status, 'dobavlj') !== false ||
			strpos($stock_status, 'naruc') !== false ||
			strpos($stock_status, 'narud') !== false ||
			strpos($stock_status, 'izdvojen') !== false
		) {
			return 'https://schema.org/BackOrder';
		}

		return 'https://schema.org/OutOfStock';
	}

	private function getCurrencyCode(): string {
		if (!empty($this->session->data['currency'])) {
			return $this->session->data['currency'];
		}

		return $this->config->get('config_currency') ?: 'EUR';
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

	private function encodeGraph(array $graph): string {
		$graph = array_values(array_filter($graph));
		$json = json_encode(array(
			'@context' => 'https://schema.org',
			'@graph' => $graph
		), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

		return $json ?: '';
	}
}
