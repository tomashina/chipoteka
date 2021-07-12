<?php
class ModelExtensionInformationParent extends Model {
	public function getInformations($parent_id = 0) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) LEFT JOIN " . DB_PREFIX . "information_to_store i2s ON (i.information_id = i2s.information_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND i.status = '1' AND i.parent_id = '". (int)$parent_id ."' ORDER BY i.sort_order, LCASE(id.title) ASC");

		return $query->rows;
	}
}