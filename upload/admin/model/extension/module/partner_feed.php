<?php
use Agmedia\Luceed\Facade\LuceedProduct;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Price;

class ModelExtensionModulePartnerFeed extends Model {
	public function install($seed_defaults = true) {
		$table_query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . "partner_feed") . "'");
		$table_existed = $table_query->num_rows > 0;

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "partner_feed` (
			`partner_feed_id` INT(11) NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(128) NOT NULL,
			`code` VARCHAR(64) NOT NULL,
			`luceed_partner_id` VARCHAR(128) NOT NULL,
			`customer_group_id` INT(11) NOT NULL,
			`access_key` VARCHAR(128) NOT NULL,
			`status` TINYINT(1) NOT NULL DEFAULT '1',
			`feed_status` TINYINT(1) NOT NULL DEFAULT '1',
			`sort_order` INT(11) NOT NULL DEFAULT '0',
			`last_sync_at` DATETIME NULL,
			`last_sync_status` VARCHAR(20) NOT NULL DEFAULT '',
			`last_sync_message` VARCHAR(255) NOT NULL DEFAULT '',
			`last_sync_count` INT(11) NOT NULL DEFAULT '0',
			`date_added` DATETIME NOT NULL,
			`date_modified` DATETIME NOT NULL,
			PRIMARY KEY (`partner_feed_id`),
			UNIQUE KEY `partner_feed_code` (`code`),
			UNIQUE KEY `partner_feed_luceed_partner_id` (`luceed_partner_id`),
			KEY `partner_feed_customer_group_id` (`customer_group_id`),
			KEY `partner_feed_status` (`status`, `feed_status`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->ensureCustomerGroupIndex();

		if (!$seed_defaults && $table_existed) {
			return;
		}

		$count_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "partner_feed`");
		if ((int)$count_query->row['total'] > 0) {
			return;
		}

		$defaults = array(
			array('N1 B2B', 'n1-b2b', '14956-N1', 3, '', 1, 0, 10),
			array('N2 B2B', 'n2-b2b', '14956-N2', 4, '', 1, 0, 20),
			array('Bazzar', 'bazzar', '80045', 5, 'gNG_p]x7V[+I=uaIgjo=hTMQX~s', 1, 1, 30),
			array('Shoppster', 'shoppster', '00983204', 6, 'd15onhQrRpq?^e@Fz*]g', 1, 1, 40)
		);

		foreach ($defaults as $default) {
			$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "partner_feed` SET
				name = '" . $this->db->escape($default[0]) . "',
				code = '" . $this->db->escape($default[1]) . "',
				luceed_partner_id = '" . $this->db->escape($default[2]) . "',
				customer_group_id = '" . (int)$default[3] . "',
				access_key = '" . $this->db->escape($default[4]) . "',
				status = '" . (int)$default[5] . "',
				feed_status = '" . (int)$default[6] . "',
				sort_order = '" . (int)$default[7] . "',
				date_added = NOW(),
				date_modified = NOW()");
		}
	}

	public function addPartnerFeed($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "partner_feed` SET
			name = '" . $this->db->escape($data['name']) . "',
			code = '" . $this->db->escape($data['code']) . "',
			luceed_partner_id = '" . $this->db->escape($data['luceed_partner_id']) . "',
			customer_group_id = '" . (int)$data['customer_group_id'] . "',
			access_key = '" . $this->db->escape($data['access_key']) . "',
			status = '" . (int)$data['status'] . "',
			feed_status = '" . (int)$data['feed_status'] . "',
			sort_order = '" . (int)$data['sort_order'] . "',
			date_added = NOW(),
			date_modified = NOW()");

		return $this->db->getLastId();
	}

	public function editPartnerFeed($partner_feed_id, $data) {
		$this->db->query("UPDATE `" . DB_PREFIX . "partner_feed` SET
			name = '" . $this->db->escape($data['name']) . "',
			code = '" . $this->db->escape($data['code']) . "',
			luceed_partner_id = '" . $this->db->escape($data['luceed_partner_id']) . "',
			customer_group_id = '" . (int)$data['customer_group_id'] . "',
			access_key = '" . $this->db->escape($data['access_key']) . "',
			status = '" . (int)$data['status'] . "',
			feed_status = '" . (int)$data['feed_status'] . "',
			sort_order = '" . (int)$data['sort_order'] . "',
			date_modified = NOW()
			WHERE partner_feed_id = '" . (int)$partner_feed_id . "'");
	}

	public function deletePartnerFeed($partner_feed_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "partner_feed` WHERE partner_feed_id = '" . (int)$partner_feed_id . "'");
	}

	public function getPartnerFeed($partner_feed_id) {
		$query = $this->db->query("SELECT pf.*, cgd.name AS customer_group_name
			FROM `" . DB_PREFIX . "partner_feed` pf
			LEFT JOIN `" . DB_PREFIX . "customer_group_description` cgd ON (cgd.customer_group_id = pf.customer_group_id AND cgd.language_id = '" . (int)$this->config->get('config_language_id') . "')
			WHERE pf.partner_feed_id = '" . (int)$partner_feed_id . "'");

		return $query->row;
	}

	public function getPartnerFeeds() {
		$query = $this->db->query("SELECT pf.*, cgd.name AS customer_group_name,
			(SELECT COUNT(*) FROM `" . DB_PREFIX . "product_discount` pd WHERE pd.customer_group_id = pf.customer_group_id) AS discount_count
			FROM `" . DB_PREFIX . "partner_feed` pf
			LEFT JOIN `" . DB_PREFIX . "customer_group_description` cgd ON (cgd.customer_group_id = pf.customer_group_id AND cgd.language_id = '" . (int)$this->config->get('config_language_id') . "')
			ORDER BY pf.sort_order ASC, pf.name ASC");

		return $query->rows;
	}

	public function getActiveMappings() {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "partner_feed` WHERE status = '1' ORDER BY sort_order ASC, partner_feed_id ASC");

		return $query->rows;
	}

	public function getCodeOwner($code) {
		$query = $this->db->query("SELECT partner_feed_id FROM `" . DB_PREFIX . "partner_feed` WHERE code = '" . $this->db->escape($code) . "' LIMIT 1");

		return $query->num_rows ? (int)$query->row['partner_feed_id'] : 0;
	}

	public function getLuceedPartnerOwner($luceed_partner_id) {
		$query = $this->db->query("SELECT partner_feed_id FROM `" . DB_PREFIX . "partner_feed` WHERE luceed_partner_id = '" . $this->db->escape($luceed_partner_id) . "' LIMIT 1");

		return $query->num_rows ? (int)$query->row['partner_feed_id'] : 0;
	}

	public function customerGroupExists($customer_group_id) {
		$query = $this->db->query("SELECT customer_group_id FROM `" . DB_PREFIX . "customer_group` WHERE customer_group_id = '" . (int)$customer_group_id . "' LIMIT 1");

		return $query->num_rows > 0;
	}

	public function regenerateAccessKey($partner_feed_id, $access_key) {
		$this->db->query("UPDATE `" . DB_PREFIX . "partner_feed` SET access_key = '" . $this->db->escape($access_key) . "', date_modified = NOW() WHERE partner_feed_id = '" . (int)$partner_feed_id . "'");
	}

	public function updateSyncResult($partner_feed_id, $status, $count, $message) {
		$this->db->query("UPDATE `" . DB_PREFIX . "partner_feed` SET
			last_sync_at = NOW(),
			last_sync_status = '" . $this->db->escape($status) . "',
			last_sync_count = '" . (int)$count . "',
			last_sync_message = '" . $this->db->escape(utf8_substr($message, 0, 255)) . "',
			date_modified = NOW()
			WHERE partner_feed_id = '" . (int)$partner_feed_id . "'");
	}

	public function syncMappings($partner_feed_ids = array()) {
		$mappings = $this->getActiveMappings();
		$selected = array();

		foreach ($mappings as $mapping) {
			if (!$partner_feed_ids || in_array((int)$mapping['partner_feed_id'], array_map('intval', $partner_feed_ids), true)) {
				$selected[] = $mapping;
			}
		}

		if (!$selected) {
			throw new Exception('Nema aktivnih Luceed partnera za sinkronizaciju.');
		}

		$prepared = array();
		$total = 0;
		$errors = array();

		// Fetch and calculate every selected partner before touching live prices.
		foreach ($selected as $mapping) {
			try {
				$response = LuceedProduct::getB2BPrices($mapping['luceed_partner_id']);

				if (!is_string($response) || trim($response) === '') {
					throw new Exception('Luceed servis nije vratio odgovor.');
				}

				$decoded = json_decode($response);
				if (json_last_error() !== JSON_ERROR_NONE || !isset($decoded->result[0]->partneri)) {
					throw new Exception('Luceed je vratio neispravan odgovor.');
				}

				$loc_price = new LOC_Price(
					$response,
					$mapping['customer_group_id']
				);
				$loc_price->collectB2B();
				$rows = $loc_price->getPricesToUpdate();

				if (!$rows->count()) {
					throw new Exception('Luceed nije vratio nijednu izračunatu cijenu.');
				}

				$prepared[$mapping['partner_feed_id']] = $rows;
				$total += $rows->count();
			} catch (Throwable $e) {
				$this->updateSyncResult($mapping['partner_feed_id'], 'error', 0, $e->getMessage());
				$errors[$mapping['partner_feed_id']] = $mapping['name'] . ': ' . $e->getMessage();
			}
		}

		if (!$prepared) {
			throw new Exception(implode(' | ', $errors));
		}

		$this->db->query('START TRANSACTION');

		try {
			foreach ($selected as $mapping) {
				if (!isset($prepared[$mapping['partner_feed_id']])) {
					continue;
				}

				$this->replaceGroupPrices(
					$mapping['customer_group_id'],
					$prepared[$mapping['partner_feed_id']]
				);
			}

			$this->db->query('COMMIT');
		} catch (Throwable $e) {
			$this->db->query('ROLLBACK');
			foreach ($selected as $mapping) {
				$this->updateSyncResult($mapping['partner_feed_id'], 'error', 0, $e->getMessage());
			}
			throw new Exception('Spremanje cijena nije uspjelo: ' . $e->getMessage(), 0, $e);
		}

		$result = array('total' => $total, 'partners' => array(), 'errors' => $errors);
		foreach ($selected as $mapping) {
			if (!isset($prepared[$mapping['partner_feed_id']])) {
				continue;
			}

			$count = $prepared[$mapping['partner_feed_id']]->count();
			$this->updateSyncResult($mapping['partner_feed_id'], 'success', $count, 'OK');
			$result['partners'][$mapping['partner_feed_id']] = $count;
		}

		return $result;
	}

	private function replaceGroupPrices($customer_group_id, $rows) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_discount` WHERE customer_group_id = '" . (int)$customer_group_id . "'");
		$values = array();

		foreach ($rows as $row) {
			$values[] = "('" . (int)$row['id'] . "', '" . (int)$customer_group_id . "', '1', '0', '" . number_format((float)$row['price'], 2, '.', '') . "', '0000-00-00', '0000-00-00')";

			if (count($values) >= 500) {
				$this->insertDiscountRows($values);
				$values = array();
			}
		}

		if ($values) {
			$this->insertDiscountRows($values);
		}
	}

	private function insertDiscountRows($values) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "product_discount`
			(product_id, customer_group_id, quantity, priority, price, date_start, date_end)
			VALUES " . implode(',', $values));
	}

	private function ensureCustomerGroupIndex() {
		$query = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "partner_feed` WHERE Key_name = 'partner_feed_customer_group_id'");

		if ($query->num_rows && !(int)$query->row['Non_unique']) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "partner_feed` DROP INDEX `partner_feed_customer_group_id`");
			$query = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "partner_feed` WHERE Key_name = 'partner_feed_customer_group_id'");
		}

		if (!$query->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "partner_feed` ADD INDEX `partner_feed_customer_group_id` (`customer_group_id`)");
		}
	}
}
