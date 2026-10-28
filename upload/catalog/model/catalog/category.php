<?php
class ModelCatalogCategory extends Model {
	public function getCategory($category_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

		return $query->row;
	}

	public function getCategories($parent_id = 0) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'  AND c.status = '1' ORDER BY c.sort_order ASC");

		return $query->rows;
	}

	public function getPromotionFilterOptions($category_id, $promotion_parent_id = 308) {
		$category_id = (int)$category_id;
		$promotion_parent_id = (int)$promotion_parent_id;

		if (!$category_id || !$promotion_parent_id) {
			return array();
		}

		$sql = "SELECT promo.category_id, promo_desc.name, promo.sort_order, COUNT(DISTINCT p.product_id) AS total
			FROM " . DB_PREFIX . "category promo
			LEFT JOIN " . DB_PREFIX . "category_description promo_desc
				ON (promo_desc.category_id = promo.category_id)
			LEFT JOIN " . DB_PREFIX . "category_to_store promo_store
				ON (promo_store.category_id = promo.category_id)
			INNER JOIN " . DB_PREFIX . "category_path promo_path
				ON (promo_path.path_id = promo.category_id)
			INNER JOIN " . DB_PREFIX . "product_to_category promo_p2c
				ON (promo_p2c.category_id = promo_path.category_id)
			INNER JOIN " . DB_PREFIX . "product p
				ON (p.product_id = promo_p2c.product_id)
			INNER JOIN " . DB_PREFIX . "product_to_store p2s
				ON (p2s.product_id = p.product_id)
			WHERE promo.parent_id = '" . $promotion_parent_id . "'
				AND promo.status = '1'
				AND promo_desc.language_id = '" . (int)$this->config->get('config_language_id') . "'
				AND promo_store.store_id = '" . (int)$this->config->get('config_store_id') . "'
				AND p.status = '1'
				AND p.price > '0'
				AND p.date_available <= NOW()
				AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
				AND EXISTS (
					SELECT 1
					FROM " . DB_PREFIX . "category_path regular_path
					INNER JOIN " . DB_PREFIX . "product_to_category regular_p2c
						ON (regular_p2c.category_id = regular_path.category_id)
					WHERE regular_path.path_id = '" . $category_id . "'
						AND regular_p2c.product_id = p.product_id
				)
			GROUP BY promo.category_id, promo_desc.name, promo.sort_order
			HAVING total > 0
			ORDER BY promo.sort_order ASC, LCASE(promo_desc.name) ASC";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getCategoryFilters($category_id) {
		$implode = array();

		$query = $this->db->query("SELECT filter_id FROM " . DB_PREFIX . "category_filter WHERE category_id = '" . (int)$category_id . "'");

		foreach ($query->rows as $result) {
			$implode[] = (int)$result['filter_id'];
		}

		$filter_group_data = array();

		if ($implode) {
			$filter_group_query = $this->db->query("SELECT DISTINCT f.filter_group_id, fgd.name, fg.sort_order FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_group fg ON (f.filter_group_id = fg.filter_group_id) LEFT JOIN " . DB_PREFIX . "filter_group_description fgd ON (fg.filter_group_id = fgd.filter_group_id) WHERE f.filter_id IN (" . implode(',', $implode) . ") AND fgd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY f.filter_group_id ORDER BY fg.sort_order, LCASE(fgd.name)");

			foreach ($filter_group_query->rows as $filter_group) {
				$filter_data = array();

				$filter_query = $this->db->query("SELECT DISTINCT f.filter_id, fd.name FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_description fd ON (f.filter_id = fd.filter_id) WHERE f.filter_id IN (" . implode(',', $implode) . ") AND f.filter_group_id = '" . (int)$filter_group['filter_group_id'] . "' AND fd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY f.sort_order, LCASE(fd.name)");

				foreach ($filter_query->rows as $filter) {
					$filter_data[] = array(
						'filter_id' => $filter['filter_id'],
						'name'      => $filter['name']
					);
				}

				if ($filter_data) {
					$filter_group_data[] = array(
						'filter_group_id' => $filter_group['filter_group_id'],
						'name'            => $filter_group['name'],
						'filter'          => $filter_data
					);
				}
			}
		}

		return $filter_group_data;
	}

	public function getCategoryLayoutId($category_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getCategoryFaqs($category_id) {
		if (!$this->hasCategoryFaqTable()) {
			return array();
		}

		$category_faq_data = array();
		$query = $this->db->query("SELECT question, answer FROM " . DB_PREFIX . "category_faq WHERE category_id = '" . (int)$category_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY sort_order ASC, category_faq_id ASC");

		foreach ($query->rows as $result) {
			$question = trim((string)$result['question']);
			$answer = trim((string)$result['answer']);

			if ($question === '' || $answer === '') {
				continue;
			}

			$category_faq_data[] = array(
				'question' => $question,
				'answer' => $answer
			);
		}

		return $category_faq_data;
	}

	private function hasCategoryFaqTable(): bool {
		static $has_table = null;

		if ($has_table !== null) {
			return $has_table;
		}

		$query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . "category_faq") . "'");
		$has_table = (bool)$query->num_rows;

		return $has_table;
	}

	public function getTotalCategoriesByCategoryId($parent_id = 0) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

		return $query->row['total'];
	}

    public function getLowestTierSubcategory($product_id) {
        $query = $this->db->query(
            "SELECT t1.category_id, t1.parent_id
            FROM `oc_category` t1
            INNER JOIN `oc_product_to_category` as ptc on ptc.category_id = t1.category_id
            INNER JOIN `oc_category_description` as cd on cd.category_id = ptc.category_id
            WHERE ptc.product_id = " . $product_id . " 
            AND NOT EXISTS (
                SELECT t2.parent_id
                FROM `oc_category` t2
                WHERE t2.parent_id = t1.category_id
                )
            LIMIT 1;"

        );

        return $query->rows[0];
    }
}
