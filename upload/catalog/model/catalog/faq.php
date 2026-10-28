<?php
class ModelCatalogFaq extends Model {
	public function getFaqCategoriesWithItems() {
		$categories = array();
		$language_id = (int)$this->config->get('config_language_id');

		$sql = "SELECT fc.faq_category_id, fcd.name, fi.faq_item_id, fi.question, fi.answer FROM " . DB_PREFIX . "faq_category fc INNER JOIN " . DB_PREFIX . "faq_category_description fcd ON (fc.faq_category_id = fcd.faq_category_id) INNER JOIN " . DB_PREFIX . "faq_item fi ON (fc.faq_category_id = fi.faq_category_id) WHERE fc.status = '1' AND fcd.language_id = '" . $language_id . "' AND fi.language_id = '" . $language_id . "' AND fi.status = '1' ORDER BY fc.sort_order ASC, LCASE(fcd.name) ASC, fi.sort_order ASC, fi.faq_item_id ASC";

		$query = $this->db->query($sql);

		foreach ($query->rows as $row) {
			$category_id = (int)$row['faq_category_id'];
			$answer_html = $this->sanitizeAnswerHtml((string)$row['answer']);

			if (!isset($categories[$category_id])) {
				$categories[$category_id] = array(
					'faq_category_id' => $category_id,
					'name'            => $this->decodeHtmlEntities((string)$row['name']),
					'items'           => array()
				);
			}

			$categories[$category_id]['items'][] = array(
				'faq_item_id' => (int)$row['faq_item_id'],
				'question'    => trim($this->decodeHtmlEntities((string)$row['question'])),
				'answer'      => $this->getAnswerText($answer_html),
				'answer_html' => $answer_html
			);
		}

		return array_values($categories);
	}

	private function sanitizeAnswerHtml($answer) {
		$answer = trim($this->decodeHtmlEntities((string)$answer));

		if ($answer === '') {
			return '';
		}

		if (!class_exists('HTMLPurifier')) {
			return nl2br(htmlspecialchars($answer, ENT_QUOTES, 'UTF-8'));
		}

		static $purifier;

		if (!$purifier) {
			$config = HTMLPurifier_Config::createDefault();
			$config->set('Cache.DefinitionImpl', null);
			$config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,ul,ol,li,blockquote,a[href|title|target|rel]');
			$config->set('Attr.AllowedFrameTargets', array('_blank'));
			$config->set('HTML.TargetNoopener', true);
			$config->set('HTML.TargetNoreferrer', true);
			$config->set('URI.AllowedSchemes', array(
				'http'   => true,
				'https'  => true,
				'mailto' => true,
				'tel'    => true
			));

			$purifier = new HTMLPurifier($config);
		}

		$contains_html = (bool)preg_match('/<\s*\/?\s*[a-z][^>]*>/i', $answer);
		$clean = $purifier->purify($answer);

		return $contains_html ? $clean : nl2br($clean);
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

	private function getAnswerText($html) {
		$text = preg_replace('/<(?:br\s*\/?|\/(?:p|li|ul|ol|blockquote))>/i', ' ', (string)$html);
		$text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
		$text = preg_replace('/\s+/u', ' ', $text);

		return trim((string)$text);
	}
}
