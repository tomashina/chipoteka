<?php
class ModelCatalogStructuredData extends Model {
	public function buildCategoryCollection(array $category_info, array $breadcrumbs, array $products, string $canonical_url, int $total_products, int $offset = 0, array $faq_items = array()): string {
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

		$faq_node = $this->buildFaqPageNode($canonical_url, $faq_items);

		if ($faq_node) {
			$graph[] = $faq_node;
		}

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

	public function buildInformationPolicyPage(array $information_info, int $information_id, array $breadcrumbs, string $canonical_url, array $faq_items = array()): string {
		$canonical_url = $this->normalizeUrl($canonical_url);
		$page_node_id = $canonical_url . '#webpage';
		$organization_node_id = $this->getStoreBaseUrl() . '/#organization';
		$title = $this->cleanText($information_info['title'] ?? '');
		$description = $this->truncateText($this->cleanText($information_info['meta_description'] ?? $information_info['description'] ?? ''), 800);
		$organization_node = $this->buildOrganizationNode($organization_node_id);
		$policy_nodes = $this->buildPolicyNodes($information_id, $canonical_url);

		foreach ($policy_nodes as $policy_node) {
			if (($policy_node['@type'] ?? '') === 'MerchantReturnPolicy' && !empty($policy_node['@id'])) {
				$organization_node['hasMerchantReturnPolicy'] = array('@id' => $policy_node['@id']);
			}
		}

		$graph = array(
			array_filter(array(
				'@type' => 'WebPage',
				'@id' => $page_node_id,
				'url' => $canonical_url,
				'name' => $title,
				'description' => $description,
				'inLanguage' => 'hr-HR',
				'isPartOf' => array('@id' => $this->getStoreBaseUrl() . '/#website'),
				'publisher' => array('@id' => $organization_node_id),
				'about' => $this->buildPolicyAbout($information_id)
			)),
			$organization_node,
			$this->buildWebsiteNode(),
			$this->buildBreadcrumbList($canonical_url, $breadcrumbs)
		);

		foreach ($policy_nodes as $policy_node) {
			$graph[] = $policy_node;
		}

		$faq_node = $this->buildFaqPageNode($canonical_url, $faq_items);

		if ($faq_node) {
			$graph[] = $faq_node;
		}

		return $this->encodeGraph($graph);
	}

	public function buildContactPage(array $locations, array $breadcrumbs, string $canonical_url, string $page_key = 'poslovnice', array $faq_items = array()): string {
		$canonical_url = $this->normalizeUrl($canonical_url);
		$page_node_id = $canonical_url . '#webpage';
		$organization_node_id = $this->getStoreBaseUrl() . '/#organization';
		$location_list = $this->buildLocationItemList($locations, $canonical_url);
		$service_node = $page_key === 'servis' ? $this->buildServiceNode($canonical_url, $organization_node_id) : array();
		$main_entity = array();

		if ($location_list) {
			$main_entity[] = array('@id' => $location_list['@id']);
		}

		if ($service_node) {
			$main_entity[] = array('@id' => $service_node['@id']);
		}

		$graph = array(
			array_filter(array(
				'@type' => 'ContactPage',
				'@id' => $page_node_id,
				'url' => $canonical_url,
				'name' => $this->getContactPageName($page_key),
				'description' => $this->getContactPageDescription($page_key),
				'inLanguage' => 'hr-HR',
				'isPartOf' => array('@id' => $this->getStoreBaseUrl() . '/#website'),
				'publisher' => array('@id' => $organization_node_id),
				'mainEntity' => $main_entity
			)),
			$this->buildOrganizationNode($organization_node_id),
			$this->buildWebsiteNode(),
			$this->buildBreadcrumbList($canonical_url, $breadcrumbs)
		);

		if ($location_list) {
			$graph[] = $location_list;
		}

		if ($service_node) {
			$graph[] = $service_node;
		}

		$faq_node = $this->buildFaqPageNode($canonical_url, $faq_items);

		if ($faq_node) {
			$graph[] = $faq_node;
		}

		return $this->encodeGraph($graph);
	}

	public function buildWebsiteSearchAction(): string {
		return $this->encodeGraph(array($this->buildWebsiteNode()));
	}

	public function buildFaqPage(string $title, string $description, array $breadcrumbs, string $canonical_url, array $faq_items): string {
		$canonical_url = $this->normalizeUrl($canonical_url);
		$organization_node_id = $this->getStoreBaseUrl() . '/#organization';
		$faq_node = $this->buildFaqPageNode($canonical_url, $faq_items);

		$page_node = array_filter(array(
			'@type' => 'WebPage',
			'@id' => $canonical_url . '#webpage',
			'url' => $canonical_url,
			'name' => $this->cleanText($title),
			'description' => $this->truncateText($this->cleanText($description), 800),
			'inLanguage' => str_replace('_', '-', (string)($this->config->get('config_language') ?: 'hr-HR')),
			'isPartOf' => array('@id' => $this->getStoreBaseUrl() . '/#website'),
			'publisher' => array('@id' => $organization_node_id),
			'mainEntity' => $faq_node ? array('@id' => $faq_node['@id']) : array()
		));

		$graph = array(
			$page_node,
			$this->buildOrganizationNode($organization_node_id),
			$this->buildWebsiteNode(),
			$this->buildBreadcrumbList($canonical_url, $breadcrumbs)
		);

		if ($faq_node) {
			$graph[] = $faq_node;
		}

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
		$offer_price = $this->getOfferPrice($product);
		$currency = $this->getCurrencyCode();
		$product_node = array_filter(array(
			'@type' => 'Product',
			'@id' => $url . '#product',
			'name' => $this->truncateText($this->cleanText($product['name'] ?? ''), 150),
			'url' => $url,
			'description' => $this->getProductListDescription($product),
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
			'validFrom' => $this->getOfferValidFrom($product),
			'priceCurrency' => $currency,
			'price' => number_format($offer_price, 2, '.', ''),
			'priceValidUntil' => date('Y-m-d', strtotime('+30 days')),
			'availability' => $this->getAvailability($product),
			'itemCondition' => 'https://schema.org/NewCondition',
			'shippingDetails' => $this->buildOfferShippingDetails($offer_price, $currency),
			'hasMerchantReturnPolicy' => $this->buildOfferReturnPolicy()
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

	private function buildOrganizationNode(string $organization_node_id): array {
		$base_url = $this->getStoreBaseUrl();
		$telephone = $this->cleanText($this->config->get('config_telephone'));
		$contact_point = array_filter(array(
			'@type' => 'ContactPoint',
			'contactType' => 'customer support',
			'url' => $base_url . '/poslovnice',
			'telephone' => $telephone,
			'areaServed' => 'HR',
			'availableLanguage' => array('hr')
		));

		return array_filter(array(
			'@type' => array('Organization', 'Store'),
			'@id' => $organization_node_id,
			'name' => $this->cleanText($this->config->get('config_name') ?: 'Chipoteka'),
			'url' => $base_url,
			'telephone' => $telephone,
			'contactPoint' => $contact_point,
			'areaServed' => array(
				'@type' => 'Country',
				'name' => 'Croatia'
			),
			'currenciesAccepted' => 'EUR'
		));
	}

	private function buildWebsiteNode(): array {
		$base_url = $this->getStoreBaseUrl();

		return array(
			'@type' => 'WebSite',
			'@id' => $base_url . '/#website',
			'name' => $this->cleanText($this->config->get('config_name') ?: 'Chipoteka'),
			'url' => $base_url,
			'inLanguage' => 'hr-HR',
			'potentialAction' => array(
				'@type' => 'SearchAction',
				'target' => array(
					'@type' => 'EntryPoint',
					'urlTemplate' => $base_url . '/index.php?route=product/search&search={search_term_string}'
				),
				'query-input' => 'required name=search_term_string'
			)
		);
	}

	private function buildPolicyNodes(int $information_id, string $canonical_url): array {
		if ($information_id !== 3) {
			return array();
		}

		return array(
			array(
				'@type' => 'MerchantReturnPolicy',
				'@id' => $canonical_url . '#return-policy',
				'name' => 'Uvjeti povrata',
				'url' => $canonical_url,
				'applicableCountry' => 'HR',
				'returnPolicyCountry' => 'HR',
				'merchantReturnDays' => 14
			)
		);
	}

	private function buildPolicyAbout(int $information_id): array {
		$topics = array();

		if ($information_id === 3) {
			$topics = array('Placanje', 'Dostava', 'Povrat', 'Jamstvo', 'Servis');
		} elseif ($information_id === 5) {
			$topics = array('Opci uvjeti koristenja', 'Online kupnja', 'Korisnicki racun');
		} elseif ($information_id === 6) {
			$topics = array('Privatnost', 'Zastita osobnih podataka', 'Korisnicka prava');
		}

		return array_map(function (string $topic): array {
			return array(
				'@type' => 'Thing',
				'name' => $topic
			);
		}, $topics);
	}

	private function buildFaqPageNode(string $canonical_url, array $faq_items): array {
		$questions = array();

		foreach ($faq_items as $item) {
			$question = $this->cleanText($item['question'] ?? '');
			$answer = $this->cleanText($item['answer'] ?? '');

			if ($question === '' || $answer === '') {
				continue;
			}

			$questions[] = array(
				'@type' => 'Question',
				'name' => $question,
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text' => $answer
				)
			);
		}

		if (!$questions) {
			return array();
		}

		return array(
			'@type' => 'FAQPage',
			'@id' => $canonical_url . '#faq',
			'mainEntity' => $questions
		);
	}

	private function buildLocationItemList(array $locations, string $canonical_url): array {
		$items = array();
		$position = 1;

		foreach ($locations as $location) {
			$store = $this->buildLocalBusinessNode($location, $canonical_url, $position);

			if (!$store) {
				continue;
			}

			$items[] = array(
				'@type' => 'ListItem',
				'position' => $position++,
				'item' => $store
			);
		}

		if (!$items) {
			return array();
		}

		return array(
			'@type' => 'ItemList',
			'@id' => $canonical_url . '#locations',
			'name' => 'Chipoteka poslovnice',
			'itemListElement' => $items
		);
	}

	private function buildLocalBusinessNode(array $location, string $canonical_url, int $position): array {
		$name = $this->cleanText($location['name'] ?? '');

		if ($name === '') {
			return array();
		}

		$image = !empty($location['image']) ? $this->normalizeUrl($location['image']) : '';

		return array_filter(array(
			'@type' => 'ElectronicsStore',
			'@id' => $canonical_url . '#location-' . $position,
			'name' => $name,
			'url' => $canonical_url,
			'image' => $image,
			'telephone' => $this->cleanText($location['telephone'] ?? ''),
			'address' => array_filter(array(
				'@type' => 'PostalAddress',
				'streetAddress' => $this->cleanText($location['address'] ?? ''),
				'addressCountry' => 'HR'
			)),
			'openingHours' => $this->cleanText($location['open'] ?? ''),
			'geo' => $this->buildGeoNode($location['geocode'] ?? '')
		));
	}

	private function buildGeoNode($geocode): array {
		$geocode = trim((string)$geocode);

		if ($geocode === '') {
			return array();
		}

		$parts = preg_split('/\s*,\s*/', $geocode);

		if (!$parts || count($parts) < 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
			return array();
		}

		return array(
			'@type' => 'GeoCoordinates',
			'latitude' => (float)$parts[0],
			'longitude' => (float)$parts[1]
		);
	}

	private function buildServiceNode(string $canonical_url, string $organization_node_id): array {
		return array(
			'@type' => 'Service',
			'@id' => $canonical_url . '#service',
			'name' => 'Chipoteka servis',
			'serviceType' => 'Servis i popravak elektronike',
			'areaServed' => 'HR',
			'provider' => array('@id' => $organization_node_id),
			'url' => $canonical_url
		);
	}

	private function getContactPageName(string $page_key): string {
		if ($page_key === 'servis') {
			return 'Chipoteka servis';
		}

		if ($page_key === 'veleprodaja') {
			return 'Chipoteka veleprodaja';
		}

		return 'Chipoteka poslovnice';
	}

	private function getContactPageDescription(string $page_key): string {
		if ($page_key === 'servis') {
			return 'Informacije o Chipoteka servisu, popravcima i provjeri statusa reklamacije.';
		}

		if ($page_key === 'veleprodaja') {
			return 'Kontakt informacije za Chipoteka veleprodaju i B2B upite.';
		}

		return 'Pregled Chipoteka poslovnica i kontakt informacija.';
	}

	private function addProductIdentifiers(array &$product_node, array $product): void {
		$this->addGtin($product_node, $product['upc'] ?? '');
		$ean = trim((string)($product['ean'] ?? ''));

		if ($ean !== '' && !$this->addGtin($product_node, $ean) && $this->isSafeMpn($ean)) {
			$product_node['mpn'] = $this->cleanText($ean);
		}
	}

	private function addGtin(array &$product_node, $identifier): bool {
		$gtin = preg_replace('/\D+/', '', (string)$identifier);

		if ($gtin === '' || !$this->isValidGtin($gtin)) {
			return false;
		}

		$property = 'gtin' . strlen($gtin);

		if (!isset($product_node[$property])) {
			$product_node[$property] = $gtin;
			return true;
		}

		return false;
	}

	private function isValidGtin(string $gtin): bool {
		if (!in_array(strlen($gtin), array(8, 12, 13, 14), true)) {
			return false;
		}

		$check_digit = (int)substr($gtin, -1);
		$payload = strrev(substr($gtin, 0, -1));
		$sum = 0;

		for ($index = 0, $length = strlen($payload); $index < $length; $index++) {
			$sum += (int)$payload[$index] * ($index % 2 === 0 ? 3 : 1);
		}

		return (10 - ($sum % 10)) % 10 === $check_digit;
	}

	private function isSafeMpn($mpn): bool {
		$mpn = $this->cleanText($mpn);

		if ($mpn === '' || strlen($mpn) > 70 || preg_match('/^\d+$/', $mpn)) {
			return false;
		}

		return !preg_match('/\b(garanc|jamstvo|warranty|dobavlj|naruc|narud|isporuk|dostav)\b/iu', $mpn);
	}

	private function getProductListDescription(array $product): string {
		$description = $this->cleanText(($product['description'] ?? '') ?: ($product['meta_description'] ?? ''));

		if ($description === '') {
			$description = $this->cleanText(implode(' ', array_filter(array(
				$product['name'] ?? '',
				$product['manufacturer'] ?? '',
				$product['model'] ?? ''
			))));
		}

		return $this->truncateText($description, 800);
	}

	private function getOfferValidFrom(array $product): string {
		foreach (array('date_available', 'date_added', 'date_modified') as $field) {
			if (empty($product[$field]) || $product[$field] === '0000-00-00' || $product[$field] === '0000-00-00 00:00:00') {
				continue;
			}

			$timestamp = strtotime((string)$product[$field]);

			if ($timestamp !== false) {
				return date('Y-m-d', $timestamp);
			}
		}

		return date('Y-m-d');
	}

	private function buildOfferShippingDetails(float $offer_price, string $currency): array {
		$shipping_price = $offer_price >= 66.36 ? 0.0 : 5.17;

		return array(
			'@type' => 'OfferShippingDetails',
			'shippingDestination' => array(
				'@type' => 'DefinedRegion',
				'addressCountry' => 'HR'
			),
			'shippingRate' => array(
				'@type' => 'MonetaryAmount',
				'value' => number_format($shipping_price, 2, '.', ''),
				'currency' => $currency
			),
			'deliveryTime' => array(
				'@type' => 'ShippingDeliveryTime',
				'handlingTime' => array(
					'@type' => 'QuantitativeValue',
					'minValue' => 0,
					'maxValue' => 1,
					'unitCode' => 'DAY'
				),
				'transitTime' => array(
					'@type' => 'QuantitativeValue',
					'minValue' => 1,
					'maxValue' => 5,
					'unitCode' => 'DAY'
				)
			)
		);
	}

	private function buildOfferReturnPolicy(): array {
		return array(
			'@type' => 'MerchantReturnPolicy',
			'applicableCountry' => 'HR',
			'returnPolicyCountry' => 'HR',
			'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
			'merchantReturnDays' => 14,
			'returnMethod' => 'https://schema.org/ReturnByMail',
			'returnFees' => 'https://schema.org/ReturnFeesCustomerResponsibility',
			'url' => $this->getStoreBaseUrl() . '/uvjeti-kupovine'
		);
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
