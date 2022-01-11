<?php
class ModelExtensionRecentPurchased extends Model {

	public function getTmdOrders() {
		$limit = $this->config->get('module_recentpurchased_limit');
		
		$start = 0;

		if (!empty($limit)) {
			$limit = $limit;
		}else{
			$limit = 4;
		}

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_product os ON (o.order_id = os.order_id) WHERE o.order_id <>0 GROUP BY os.product_id ORDER BY o.order_id DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}
	
}