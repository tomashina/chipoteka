<?php
class ModelExtensionFeedOpenaiProductFeed extends Model {
	public function getProducts(int $start = 0, int $limit = 500): array {
		$start = max(0, $start);
		$limit = max(1, $limit);

		$sql = "SELECT p.product_id, p.model, p.sku, p.upc, p.ean, p.mpn, p.image, p.quantity, p.quantityws, p.price, p.price_2, p.price_last_30, p.tax_class_id, p.weight, p.weight_class_id, p.length, p.width, p.height, p.length_class_id, p.manufacturer_id, p.date_available, p.date_modified, pd.name, pd.description, pd.meta_description, m.name AS manufacturer, ss.name AS stock_status, wcd.unit AS weight_class, lcd.unit AS length_class, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00 00:00:00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00 00:00:00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT AVG(rating) FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT COUNT(*) FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) LEFT JOIN " . DB_PREFIX . "stock_status ss ON (p.stock_status_id = ss.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN " . DB_PREFIX . "weight_class_description wcd ON (p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN " . DB_PREFIX . "length_class_description lcd ON (p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "') WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.price > '0' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.product_id ASC LIMIT " . (int)$start . "," . (int)$limit;

		$query = $this->db->query($sql);
		$products = $query->rows;

		if (!$products) {
			return array();
		}

		$product_ids = array();

		foreach ($products as $product) {
			$product_ids[] = (int)$product['product_id'];
		}

		$additional_images = $this->getAdditionalImages($product_ids);
		$category_paths = $this->getCategoryPaths($product_ids);

		foreach ($products as &$product) {
			$product_id = (int)$product['product_id'];
			$product['additional_images'] = $additional_images[$product_id] ?? array();
			$product['product_category'] = $category_paths[$product_id] ?? '';
		}

		unset($product);

		return $products;
	}

	private function getAdditionalImages(array $product_ids): array {
		$ids = $this->normalizeProductIds($product_ids);

		if (!$ids) {
			return array();
		}

		$query = $this->db->query("SELECT product_id, image FROM " . DB_PREFIX . "product_image WHERE product_id IN (" . implode(',', $ids) . ") AND image <> '' ORDER BY product_id ASC, sort_order ASC");
		$images = array();

		foreach ($query->rows as $row) {
			$product_id = (int)$row['product_id'];

			if (!isset($images[$product_id])) {
				$images[$product_id] = array();
			}

			$images[$product_id][] = $row['image'];
		}

		return $images;
	}

	private function getCategoryPaths(array $product_ids): array {
		$ids = $this->normalizeProductIds($product_ids);

		if (!$ids) {
			return array();
		}

		$sql = "SELECT p2c.product_id, p2c.category_id, cp.level, cd.name FROM " . DB_PREFIX . "product_to_category p2c LEFT JOIN " . DB_PREFIX . "category c ON (p2c.category_id = c.category_id) LEFT JOIN " . DB_PREFIX . "category_path cp ON (p2c.category_id = cp.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd ON (cp.path_id = cd.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "') WHERE p2c.product_id IN (" . implode(',', $ids) . ") AND c.status = '1' ORDER BY p2c.product_id ASC, c.sort_order ASC, p2c.category_id ASC, cp.level ASC";
		$query = $this->db->query($sql);
		$paths = array();
		$leaf_category = array();

		foreach ($query->rows as $row) {
			$product_id = (int)$row['product_id'];
			$category_id = (int)$row['category_id'];

			if (!isset($leaf_category[$product_id])) {
				$leaf_category[$product_id] = $category_id;
				$paths[$product_id] = array();
			}

			if ($leaf_category[$product_id] !== $category_id) {
				continue;
			}

			$name = trim((string)$row['name']);

			if ($name !== '') {
				$paths[$product_id][] = $name;
			}
		}

		foreach ($paths as $product_id => $path) {
			$paths[$product_id] = implode(' > ', array_unique($path));
		}

		return $paths;
	}

	private function normalizeProductIds(array $product_ids): array {
		$ids = array();

		foreach ($product_ids as $product_id) {
			$product_id = (int)$product_id;

			if ($product_id > 0) {
				$ids[] = $product_id;
			}
		}

		return array_values(array_unique($ids));
	}
}
