<?php
class ModelCatalogInformation extends Model {
	public function getInformation($information_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) LEFT JOIN " . DB_PREFIX . "information_to_store i2s ON (i.information_id = i2s.information_id) WHERE i.information_id = '" . (int)$information_id . "' AND id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND i.status = '1'");

		return $query->row;
	}

	public function getInformations() {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (i.information_id = id.information_id) LEFT JOIN " . DB_PREFIX . "information_to_store i2s ON (i.information_id = i2s.information_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND i.status = '1' ORDER BY i.sort_order, LCASE(id.title) ASC");

		return $query->rows;
	}

	public function getInformationLayoutId($information_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "information_to_layout WHERE information_id = '" . (int)$information_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getInformationFaqs($information_id) {
		if (!$this->hasInformationFaqTable()) {
			return array();
		}

		$information_faq_data = array();
		$query = $this->db->query("SELECT question, answer FROM " . DB_PREFIX . "information_faq WHERE information_id = '" . (int)$information_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY sort_order ASC, information_faq_id ASC");

		foreach ($query->rows as $result) {
			$question = trim((string)$result['question']);
			$answer = trim((string)$result['answer']);

			if ($question === '' || $answer === '') {
				continue;
			}

			$information_faq_data[] = array(
				'question' => $question,
				'answer' => $answer
			);
		}

		return $information_faq_data;
	}

	private function hasInformationFaqTable(): bool {
		static $has_table = null;

		if ($has_table !== null) {
			return $has_table;
		}

		$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . "information_faq") . "'");
		$has_table = (bool)$query->num_rows;

		return $has_table;
	}
}
