<?php
class ModelAccountContractWithdrawal extends Model {
	public function getCustomerOrder($order_id) {
		if (!$this->customer->isLogged()) {
			return false;
		}

		$sql = "SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "' AND order_status_id > '0' AND store_id = '" . (int)$this->config->get('config_store_id') . "'";

		if (method_exists($this->customer, 'getMaster') && $this->customer->getMaster() == '1' && method_exists($this->customer, 'getGrupapartnera')) {
			$sql .= " AND grupa_partnera = '" . $this->db->escape($this->customer->getGrupapartnera()) . "' AND customer_id != '0'";
		} else {
			$sql .= " AND customer_id = '" . (int)$this->customer->getId() . "' AND customer_id != '0'";
		}

		$query = $this->db->query($sql);

		return $query->num_rows ? $query->row : false;
	}

	public function getOrderByEmail($order_id, $email) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "' AND LCASE(email) = '" . $this->db->escape(utf8_strtolower($email)) . "' AND order_status_id > '0' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		return $query->num_rows ? $query->row : false;
	}

	public function getOrderProducts($order_id) {
		$query = $this->db->query("SELECT order_product_id, product_id, name, model, quantity FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "' ORDER BY order_product_id ASC");

		return $query->rows;
	}

	public function addWithdrawal($data) {
		$date_ordered = !empty($data['date_ordered']) ? "'" . $this->db->escape($data['date_ordered']) . "'" : "NULL";

		$this->db->query("INSERT INTO `" . DB_PREFIX . "contract_withdrawal` SET store_id = '" . (int)$this->config->get('config_store_id') . "', order_id = '" . (int)$data['order_id'] . "', customer_id = '" . (int)$data['customer_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', address = '" . $this->db->escape($data['address']) . "', withdrawal_scope = '" . $this->db->escape($data['withdrawal_scope']) . "', products = '" . $this->db->escape(json_encode($data['products'])) . "', statement = '" . $this->db->escape($data['statement']) . "', comment = '" . $this->db->escape($data['comment']) . "', status = 'new', token = '" . $this->db->escape($data['token']) . "', ip = '" . $this->db->escape($data['ip']) . "', user_agent = '" . $this->db->escape($data['user_agent']) . "', date_ordered = " . $date_ordered . ", date_submitted = NOW(), date_modified = NOW()");

		$contract_withdrawal_id = $this->db->getLastId();

		$this->addHistory($contract_withdrawal_id, 'new', $data['statement'], 1, 0);

		return $contract_withdrawal_id;
	}

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

	public function getWithdrawalByToken($contract_withdrawal_id, $token) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "contract_withdrawal` WHERE contract_withdrawal_id = '" . (int)$contract_withdrawal_id . "' AND token = '" . $this->db->escape($token) . "'");

		if ($query->num_rows) {
			$query->row['products'] = json_decode($query->row['products'], true);

			if (!is_array($query->row['products'])) {
				$query->row['products'] = array();
			}

			return $query->row;
		}

		return false;
	}

	public function addHistory($contract_withdrawal_id, $status, $comment, $notify = 0, $user_id = 0) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "contract_withdrawal_history` SET contract_withdrawal_id = '" . (int)$contract_withdrawal_id . "', status = '" . $this->db->escape($status) . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', user_id = '" . (int)$user_id . "', date_added = NOW()");
	}
}
