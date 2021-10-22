<?php
class ModelExtensionGoogleAnalyticsExpert extends Model {
	public function getStores() {
		$query = $this->db->query("SELECT store_id, value AS 'store_name' FROM " . DB_PREFIX . "setting WHERE `key` = 'config_name' ORDER BY store_id ASC");
			
		return $query->rows;
	}
	
	public function getSelectedStoreName($store_id) {
		$query = $this->db->query("SELECT value AS 'store_name' FROM " . DB_PREFIX . "setting WHERE store_id = '" . $this->db->escape($store_id) . "' AND `key` = 'config_name'");
			
		return $query->row['store_name'];
	}
		
	public function getSettings($store_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = 'analytics_google_analytics_expert' AND store_id = '" . $this->db->escape($store_id) . "'");
		
		if ($query->rows) {
			foreach ($query->rows as $result) {
				$settings[$result['key']] = $result['value'];
			}
			
			return $settings;
		}
	}
}