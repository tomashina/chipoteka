<?php
class ModelSaleContractWithdrawal extends Model {
	public function getWithdrawal($contract_withdrawal_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "contract_withdrawal` WHERE contract_withdrawal_id = '" . (int)$contract_withdrawal_id . "'");

		if ($query->num_rows) {
			$query->row['products'] = json_decode($query->row['products'], true);

			if (!is_array($query->row['products'])) {
				$query->row['products'] = array();
			}

			return $query->row;
		}

		return false;
	}

	public function getWithdrawals($data = array()) {
		$sql = "SELECT * FROM `" . DB_PREFIX . "contract_withdrawal` cw";
		$implode = array();

		if (!empty($data['filter_contract_withdrawal_id'])) {
			$implode[] = "cw.contract_withdrawal_id = '" . (int)$data['filter_contract_withdrawal_id'] . "'";
		}

		if (!empty($data['filter_order_id'])) {
			$implode[] = "cw.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$implode[] = "CONCAT(cw.firstname, ' ', cw.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_email'])) {
			$implode[] = "cw.email LIKE '%" . $this->db->escape($data['filter_email']) . "%'";
		}

		if (!empty($data['filter_status'])) {
			$implode[] = "cw.status = '" . $this->db->escape($data['filter_status']) . "'";
		}

		if (!empty($data['filter_date_submitted'])) {
			$implode[] = "DATE(cw.date_submitted) = DATE('" . $this->db->escape($data['filter_date_submitted']) . "')";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$sort_data = array(
			'cw.contract_withdrawal_id',
			'cw.order_id',
			'customer',
			'cw.email',
			'cw.status',
			'cw.date_submitted',
			'cw.date_modified'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'customer') {
				$sql .= " ORDER BY CONCAT(cw.firstname, ' ', cw.lastname)";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY cw.contract_withdrawal_id";
		}

		if (isset($data['order']) && $data['order'] == 'ASC') {
			$sql .= " ASC";
		} else {
			$sql .= " DESC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalWithdrawals($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "contract_withdrawal` cw";
		$implode = array();

		if (!empty($data['filter_contract_withdrawal_id'])) {
			$implode[] = "cw.contract_withdrawal_id = '" . (int)$data['filter_contract_withdrawal_id'] . "'";
		}

		if (!empty($data['filter_order_id'])) {
			$implode[] = "cw.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$implode[] = "CONCAT(cw.firstname, ' ', cw.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_email'])) {
			$implode[] = "cw.email LIKE '%" . $this->db->escape($data['filter_email']) . "%'";
		}

		if (!empty($data['filter_status'])) {
			$implode[] = "cw.status = '" . $this->db->escape($data['filter_status']) . "'";
		}

		if (!empty($data['filter_date_submitted'])) {
			$implode[] = "DATE(cw.date_submitted) = DATE('" . $this->db->escape($data['filter_date_submitted']) . "')";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function addHistory($contract_withdrawal_id, $status, $comment, $notify, $user_id) {
		$this->db->query("UPDATE `" . DB_PREFIX . "contract_withdrawal` SET status = '" . $this->db->escape($status) . "', date_modified = NOW() WHERE contract_withdrawal_id = '" . (int)$contract_withdrawal_id . "'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "contract_withdrawal_history` SET contract_withdrawal_id = '" . (int)$contract_withdrawal_id . "', status = '" . $this->db->escape($status) . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', user_id = '" . (int)$user_id . "', date_added = NOW()");
	}

	public function getHistories($contract_withdrawal_id) {
		$query = $this->db->query("SELECT cwh.*, u.username FROM `" . DB_PREFIX . "contract_withdrawal_history` cwh LEFT JOIN `" . DB_PREFIX . "user` u ON (cwh.user_id = u.user_id) WHERE cwh.contract_withdrawal_id = '" . (int)$contract_withdrawal_id . "' ORDER BY cwh.date_added DESC");

		return $query->rows;
	}
}
