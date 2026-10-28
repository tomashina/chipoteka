<?php
class ModelExtensionFeedPartnerFeed extends Model {
	private $table_exists;

	public function getPartnerByCode($code) {
		if ($this->tableExists()) {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "partner_feed` WHERE code = '" . $this->db->escape($code) . "' AND feed_status = '1' LIMIT 1");

			return $query->row;
		}

		$legacy = array(
			'bazzar' => array(
				'code' => 'bazzar',
				'customer_group_id' => 5,
				'access_key' => 'gNG_p]x7V[+I=uaIgjo=hTMQX~s',
				'status' => 1,
				'feed_status' => 1
			),
			'shoppster' => array(
				'code' => 'shoppster',
				'customer_group_id' => 6,
				'access_key' => 'd15onhQrRpq?^e@Fz*]g',
				'status' => 1,
				'feed_status' => 1
			)
		);

		return isset($legacy[$code]) ? $legacy[$code] : array();
	}

	public function getFeedProducts($customer_group_id) {
		$customer_group_id = (int)$customer_group_id;
		$default_customer_group_id = (int)$this->config->get('config_customer_group_id');
		$language_id = (int)$this->config->get('config_language_id');
		$store_id = (int)$this->config->get('config_store_id');

		$sql = "SELECT p.product_id, p.model, p.upc, p.quantityws, p.image, p.vpc,
			pd.name, pd.description, m.name AS manufacturer,
			COALESCE((
				SELECT pd_default.price FROM `" . DB_PREFIX . "product_discount` pd_default
				WHERE pd_default.product_id = p.product_id
					AND pd_default.customer_group_id = '" . $default_customer_group_id . "'
					AND pd_default.quantity = '1'
					AND ((pd_default.date_start = '0000-00-00' OR pd_default.date_start < NOW())
					AND (pd_default.date_end = '0000-00-00' OR pd_default.date_end > NOW()))
				ORDER BY pd_default.priority ASC, pd_default.price ASC LIMIT 1
			), p.price) AS price,
			(
				SELECT ps.price FROM `" . DB_PREFIX . "product_special` ps
				WHERE ps.product_id = p.product_id
					AND ps.customer_group_id = '" . $default_customer_group_id . "'
					AND ((ps.date_start = '0000-00-00 00:00:00' OR ps.date_start < NOW())
					AND (ps.date_end = '0000-00-00 00:00:00' OR ps.date_end > NOW()))
				ORDER BY ps.priority ASC, ps.price ASC LIMIT 1
			) AS special,
			(
				SELECT partner_discount.price FROM `" . DB_PREFIX . "product_discount` partner_discount
				WHERE partner_discount.product_id = p.product_id
					AND partner_discount.customer_group_id = '" . $customer_group_id . "'
					AND partner_discount.quantity = '1'
					AND ((partner_discount.date_start = '0000-00-00' OR partner_discount.date_start < NOW())
					AND (partner_discount.date_end = '0000-00-00' OR partner_discount.date_end > NOW()))
				ORDER BY partner_discount.priority ASC, partner_discount.price ASC LIMIT 1
			) AS partner_price
			FROM `" . DB_PREFIX . "product` p
			INNER JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id = p.product_id AND pd.language_id = '" . $language_id . "')
			INNER JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p2s.product_id = p.product_id AND p2s.store_id = '" . $store_id . "')
			LEFT JOIN `" . DB_PREFIX . "manufacturer` m ON (m.manufacturer_id = p.manufacturer_id)
			WHERE p.status = '1'
				AND p.price > '0'
				AND p.date_available <= NOW()
				AND p.quantityws > '0'
			HAVING partner_price IS NOT NULL AND partner_price > '0'
			ORDER BY p.sort_order ASC, LCASE(pd.name) ASC";

		$query = $this->db->query($sql);
		$products = $query->rows;
		$category_names = $this->getCategoryNames(array_column($products, 'product_id'));

		foreach ($products as &$product) {
			$product['category'] = isset($category_names[$product['product_id']]) ? $category_names[$product['product_id']] : '';
		}
		unset($product);

		return $products;
	}

	private function getCategoryNames($product_ids) {
		$ids = array();
		foreach ($product_ids as $product_id) {
			if ((int)$product_id > 0) {
				$ids[] = (int)$product_id;
			}
		}

		if (!$ids) {
			return array();
		}

		$query = $this->db->query("SELECT p2c.product_id, p2c.category_id, cd.name,
			parent_cd.name AS parent_name
			FROM `" . DB_PREFIX . "product_to_category` p2c
			INNER JOIN `" . DB_PREFIX . "category` c ON (c.category_id = p2c.category_id AND c.status = '1')
			INNER JOIN `" . DB_PREFIX . "category_to_store` c2s ON (c2s.category_id = c.category_id AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "')
			INNER JOIN `" . DB_PREFIX . "category_description` cd ON (cd.category_id = c.category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "')
			LEFT JOIN `" . DB_PREFIX . "category` parent_c ON (parent_c.category_id = c.parent_id AND parent_c.status = '1')
			LEFT JOIN `" . DB_PREFIX . "category_to_store` parent_c2s ON (parent_c2s.category_id = parent_c.category_id AND parent_c2s.store_id = '" . (int)$this->config->get('config_store_id') . "')
			LEFT JOIN `" . DB_PREFIX . "category_description` parent_cd ON (parent_cd.category_id = parent_c.category_id AND parent_cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND parent_c2s.category_id IS NOT NULL)
			WHERE p2c.product_id IN (" . implode(',', array_unique($ids)) . ")
			ORDER BY p2c.product_id ASC, p2c.category_id ASC");

		$names = array();
		foreach ($query->rows as $row) {
			$names[$row['product_id']] = $row['parent_name'] ? $row['parent_name'] . ' > ' . $row['name'] : $row['name'];
		}

		return $names;
	}

	private function tableExists() {
		if ($this->table_exists === null) {
			$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . "partner_feed") . "'");
			$this->table_exists = $query->num_rows > 0;
		}

		return $this->table_exists;
	}
}
