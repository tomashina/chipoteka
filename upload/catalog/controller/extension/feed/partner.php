<?php
class ControllerExtensionFeedPartner extends Controller {
	private const PUBLIC_BASE_URL = 'https://chipoteka.pluschip.lin29.host25.com/';
	private const PRODUCT_BASE_URL = 'https://www.chipoteka.hr';

	public function index() {
		$this->render(array(
			'code' => isset($this->request->get['code']) ? $this->request->get['code'] : '',
			'key_parameter' => 'key'
		));
	}

	public function render($data) {
		$code = isset($data['code']) ? (string)$data['code'] : '';
		$key_parameter = isset($data['key_parameter']) ? (string)$data['key_parameter'] : 'key';
		$provided_key = isset($this->request->get[$key_parameter]) ? (string)$this->request->get[$key_parameter] : '';

		// PHP decodes a literal + in a query string as a space. Preserve legacy Bazzar URLs.
		if ($key_parameter === 'pass') {
			$provided_key = str_replace(' ', '+', $provided_key);
		}

		$this->load->model('extension/feed/partner_feed');
		$partner = $this->model_extension_feed_partner_feed->getPartnerByCode($code);

		if (!$partner) {
			return $this->deny(404, 'Feed nije pronađen.');
		}

		if (!$provided_key || !$partner['access_key'] || !$this->keysMatch($partner['access_key'], $provided_key)) {
			return $this->deny(403, 'Neispravan pristupni ključ.');
		}

		$products = $this->model_extension_feed_partner_feed->getFeedProducts($partner['customer_group_id']);
		$output = '<?xml version="1.0" encoding="UTF-8"?>';
		$output .= '<products>';

		foreach ($products as $product) {
			$description = $this->cleanXml($product['description']);
			$short_description = $this->cleanXml($this->ellipsis($description));
			$name = strip_tags(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'));
			$name = str_replace(array('&nbsp;', "\x1E", "\x1F", '&#44', "'", "\x1C", "\x07", '>', '<'), '', $name);
			$name = $this->cleanXml($name);
			$price = ($product['special'] === null || $product['special'] === '') ? $product['price'] : $product['special'];

			$output .= '<product>';
			$output .= '<sifra>' . $this->xml($product['model']) . '</sifra>';
			$output .= '<ean>' . $this->xml($product['upc']) . '</ean>';
			$output .= '<kolicina>' . (int)$product['quantityws'] . '</kolicina>';
			$output .= '<name>' . $this->cdata($name) . '</name>';
			$output .= '<brand>' . $this->cdata($product['manufacturer']) . '</brand>';
			$output .= '<kratki_opis>' . $this->cdata($short_description) . '</kratki_opis>';
			$output .= '<mpc>' . number_format((float)$price, 2, '.', '') . '</mpc>';
			$output .= '<vpc>' . number_format((float)$product['vpc'], 2, '.', '') . '</vpc>';
			$output .= '<nabavna_cijena>' . number_format((float)$product['partner_price'], 2, '.', '') . '</nabavna_cijena>';
			$output .= '<url>' . $this->xml($this->productUrl($product['product_id'])) . '</url>';
			$output .= '<category>' . $this->cdata($product['category']) . '</category>';
			$output .= '<image_url>' . $this->cdata(self::PUBLIC_BASE_URL . 'image/' . ltrim($product['image'], '/')) . '</image_url>';
			$output .= '<description>' . $this->cdata($description) . '</description>';
			$output .= '</product>';
		}

		$output .= '</products>';
		$output = iconv('UTF-8', 'UTF-8//IGNORE', $output);

		$this->response->addHeader('Content-Type: application/xml; charset=UTF-8');
		$this->response->addHeader('X-Content-Type-Options: nosniff');
		$this->response->addHeader('Cache-Control: private, no-store');
		$this->response->setOutput($output);
	}

	private function deny($status_code, $message) {
		$protocol = isset($this->request->server['SERVER_PROTOCOL']) ? $this->request->server['SERVER_PROTOCOL'] : 'HTTP/1.1';
		$status_text = $status_code === 404 ? 'Not Found' : 'Forbidden';
		$this->response->addHeader($protocol . ' ' . $status_code . ' ' . $status_text);
		$this->response->addHeader('Content-Type: text/plain; charset=UTF-8');
		$this->response->setOutput($message);
	}

	private function keysMatch($expected, $provided) {
		return function_exists('hash_equals') ? hash_equals((string)$expected, (string)$provided) : (string)$expected === (string)$provided;
	}

	private function productUrl($product_id) {
		$generated_url = $this->url->link('product/product', 'product_id=' . (int)$product_id, true);
		$generated_url = html_entity_decode($generated_url, ENT_QUOTES, 'UTF-8');
		$product_url = preg_replace('#^https?://[^/]+#i', self::PRODUCT_BASE_URL, $generated_url);

		return $product_url ? $product_url : self::PRODUCT_BASE_URL . '/index.php?route=product/product&product_id=' . (int)$product_id;
	}

	private function cdata($value) {
		$value = $this->cleanXml($value);
		return '<![CDATA[ ' . str_replace(']]>', ']]]]><![CDATA[>', $value) . ' ]]>';
	}

	private function xml($value) {
		return htmlspecialchars($this->cleanXml($value), ENT_QUOTES | ENT_XML1, 'UTF-8');
	}

	private function cleanXml($value) {
		$value = (string)$value;
		if (!preg_match('//u', $value)) {
			$converted = iconv('UTF-8', 'UTF-8//IGNORE', $value);
			$value = $converted === false ? '' : $converted;
		}

		return preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', ' ', $value);
	}

	private function ellipsis($text, $words = 30) {
		if (str_word_count($text) > $words) {
			preg_match("/(?:[^\s,\.;\?\!]+(?:[\s,\.;\?\!]+|$)){0,$words}/", $text, $matches);
			$text = isset($matches[0]) ? trim($matches[0]) : $text;
			$text = rtrim($text, ',.');
			$text .= '...';
		}

		return $text;
	}
}
