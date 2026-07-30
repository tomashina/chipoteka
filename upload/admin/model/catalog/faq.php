<?php
class ModelCatalogFaq extends Model {
	public function addFaqCategory($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "faq_category SET sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_added = NOW(), date_modified = NOW()");

		$faq_category_id = $this->db->getLastId();

		$this->saveFaqCategoryDescriptions($faq_category_id, $data['faq_category_description']);
		$this->saveFaqItems($faq_category_id, $data['faq_item'] ?? array());

		return $faq_category_id;
	}

	public function editFaqCategory($faq_category_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "faq_category SET sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW() WHERE faq_category_id = '" . (int)$faq_category_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "faq_category_description WHERE faq_category_id = '" . (int)$faq_category_id . "'");
		$this->saveFaqCategoryDescriptions($faq_category_id, $data['faq_category_description']);

		$this->db->query("DELETE FROM " . DB_PREFIX . "faq_item WHERE faq_category_id = '" . (int)$faq_category_id . "'");
		$this->saveFaqItems($faq_category_id, $data['faq_item'] ?? array());
	}

	public function deleteFaqCategory($faq_category_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "faq_item WHERE faq_category_id = '" . (int)$faq_category_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "faq_category_description WHERE faq_category_id = '" . (int)$faq_category_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "faq_category WHERE faq_category_id = '" . (int)$faq_category_id . "'");
	}

	public function getFaqCategory($faq_category_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "faq_category WHERE faq_category_id = '" . (int)$faq_category_id . "'");

		return $query->row;
	}

	public function getFaqCategories($data = array()) {
		$sql = "SELECT fc.faq_category_id, fcd.name, fc.sort_order, fc.status, COUNT(fi.faq_item_id) AS total_items FROM " . DB_PREFIX . "faq_category fc LEFT JOIN " . DB_PREFIX . "faq_category_description fcd ON (fc.faq_category_id = fcd.faq_category_id AND fcd.language_id = '" . (int)$this->config->get('config_language_id') . "') LEFT JOIN " . DB_PREFIX . "faq_item fi ON (fc.faq_category_id = fi.faq_category_id) GROUP BY fc.faq_category_id, fcd.name, fc.sort_order, fc.status";

		$sort_data = array(
			'fcd.name',
			'fc.sort_order',
			'fc.status'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data, true)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY fc.sort_order";
		}

		$sql .= (isset($data['order']) && $data['order'] === 'DESC') ? ' DESC' : ' ASC';
		$sql .= ", LCASE(fcd.name) ASC";

		if (isset($data['start']) || isset($data['limit'])) {
			$start = max(0, (int)($data['start'] ?? 0));
			$limit = max(1, (int)($data['limit'] ?? 20));
			$sql .= " LIMIT " . $start . "," . $limit;
		}

		$query = $this->db->query($sql);

		foreach ($query->rows as &$row) {
			$row['name'] = $this->decodeHtmlEntities((string)$row['name']);
		}
		unset($row);

		return $query->rows;
	}

	public function getTotalFaqCategories() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "faq_category");

		return (int)$query->row['total'];
	}

	public function getFaqCategoryDescriptions($faq_category_id) {
		$data = array();
		$query = $this->db->query("SELECT language_id, name FROM " . DB_PREFIX . "faq_category_description WHERE faq_category_id = '" . (int)$faq_category_id . "'");

		foreach ($query->rows as $result) {
			$data[(int)$result['language_id']] = array(
				'name' => $this->decodeHtmlEntities((string)$result['name'])
			);
		}

		return $data;
	}

	public function getFaqItems($faq_category_id) {
		$data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "faq_item WHERE faq_category_id = '" . (int)$faq_category_id . "' ORDER BY language_id ASC, sort_order ASC, faq_item_id ASC");

		foreach ($query->rows as $result) {
			$data[(int)$result['language_id']][] = array(
				'question'   => $this->decodeHtmlEntities((string)$result['question']),
				'answer'     => $this->decodeHtmlEntities((string)$result['answer']),
				'sort_order' => (int)$result['sort_order'],
				'status'     => (int)$result['status']
			);
		}

		return $data;
	}

	private function saveFaqCategoryDescriptions($faq_category_id, array $descriptions) {
		foreach ($descriptions as $language_id => $description) {
			$name = $this->decodeHtmlEntities(trim((string)$description['name']));

			$this->db->query("INSERT INTO " . DB_PREFIX . "faq_category_description SET faq_category_id = '" . (int)$faq_category_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($name) . "'");
		}
	}

	private function saveFaqItems($faq_category_id, array $items) {
		foreach ($items as $language_id => $language_items) {
			if (!is_array($language_items)) {
				continue;
			}

			foreach ($language_items as $item) {
				$question = $this->decodeHtmlEntities(trim((string)($item['question'] ?? '')));
				$answer = $this->decodeHtmlEntities(trim((string)($item['answer'] ?? '')));

				if ($question === '' || $answer === '') {
					continue;
				}

				$this->db->query("INSERT INTO " . DB_PREFIX . "faq_item SET faq_category_id = '" . (int)$faq_category_id . "', language_id = '" . (int)$language_id . "', question = '" . $this->db->escape($question) . "', answer = '" . $this->db->escape($answer) . "', sort_order = '" . (int)($item['sort_order'] ?? 0) . "', status = '" . (isset($item['status']) ? (int)$item['status'] : 1) . "', date_added = NOW(), date_modified = NOW()");
			}
		}
	}

	private function decodeHtmlEntities($value) {
		$value = (string)$value;

		for ($i = 0; $i < 5; $i++) {
			$decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

			if ($decoded === $value) {
				break;
			}

			$value = $decoded;
		}

		return $value;
	}
}
