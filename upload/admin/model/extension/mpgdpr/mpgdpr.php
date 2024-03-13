<?php
use mpgdpr\Model;
class ModelExtensionMpGdprMpGdpr extends Model {

	public function deleteRequestList($mpgdpr_requestlist_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "mpgdpr_requestlist SET status=0 WHERE mpgdpr_requestlist_id = '" . (int)$mpgdpr_requestlist_id . "'");
	}

	public function getTotalRequests($data) {
		$sql = "SELECT COUNT(DISTINCT r.mpgdpr_requestlist_id) AS total FROM " . DB_PREFIX . "mpgdpr_requestlist r WHERE r.mpgdpr_requestlist_id>0 AND r.status=1";

		if (!empty($data['filter_request_id'])) {
			$sql .= " AND r.mpgdpr_requestlist_id = '" . (int)$data['filter_request_id'] . "'";
		}

		if (!empty($data['filter_type'])) {
			$sql .= " AND r.requessttype = '" . $this->db->escape($data['filter_type']) . "'";
		}
		if (!empty($data['filter_email'])) {
			$sql .= " AND (r.customer_id IN (SELECT customer_id FROM " . DB_PREFIX . "customer c WHERE lower(c.email) LIKE '". $this->db->escape(utf8_strtolower($data['filter_email'])) ."%' ) OR lower(r.email) LIKE '". $this->db->escape(utf8_strtolower($data['filter_email'])) ."%' )";
		}
		if (!empty($data['filter_useragent'])) {
			$sql .= " AND r.user_agent = '" . $this->db->escape($data['filter_useragent']) . "'";
		}
		/*13 sep 2019 gdpr session starts*/
		if (!empty($data['filter_server_ip'])) {
			$sql .= " AND r.server_ip LIKE '%" . $this->db->escape($data['filter_server_ip']) . "%'";
		}
		if (!empty($data['filter_client_ip'])) {
			$sql .= " AND r.client_ip = '%" . $this->db->escape($data['filter_client_ip']) . "%'";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_date_start']) && empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
		}
		if (empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}
		if (!empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_date_start']) . "') AND DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}
		/*13 sep 2019 gdpr session starts*/
		// get past xx days/month/years requests
		if (!empty($data['filter_time_lap_value']) && !empty($data['filter_time_lap'])) {
			$sql .= " AND r.date_added >= DATE(NOW()) + INTERVAL - ". (int)$data['filter_time_lap_value'] ." ". $this->db->escape($data['filter_time_lap']) ." AND r.date_added <  DATE(NOW()) + INTERVAL 1 DAY ";
		}
		/*13 sep 2019 gdpr session ends*/
		$query = $this->db->query($sql);

		return $query->row['total'];
	}
	public function getRequests($data) {

		$sql = "SELECT * FROM " . DB_PREFIX . "mpgdpr_requestlist r WHERE r.mpgdpr_requestlist_id>0 AND r.status=1";

		if (!empty($data['filter_request_id'])) {
			$sql .= " AND r.mpgdpr_requestlist_id = '" . (int)$data['filter_request_id'] . "'";
		}
		if (!empty($data['filter_type'])) {
			$sql .= " AND r.requessttype = '" . $this->db->escape($data['filter_type']) . "'";
		}
		if (!empty($data['filter_email'])) {
			$sql .= " AND (r.customer_id IN (SELECT customer_id FROM " . DB_PREFIX . "customer c WHERE lower(c.email) LIKE '". $this->db->escape(utf8_strtolower($data['filter_email'])) ."%' ) OR lower(r.email) LIKE '". $this->db->escape(utf8_strtolower($data['filter_email'])) ."%' )";
		}

		if (!empty($data['filter_useragent'])) {
			$sql .= " AND r.user_agent = '" . $this->db->escape($data['filter_useragent']) . "'";
		}
		/*13 sep 2019 gdpr session starts*/
		if (!empty($data['filter_server_ip'])) {
			$sql .= " AND r.server_ip LIKE '%" . $this->db->escape($data['filter_server_ip']) . "%'";
		}
		if (!empty($data['filter_client_ip'])) {
			$sql .= " AND r.client_ip = '%" . $this->db->escape($data['filter_client_ip']) . "%'";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_date_start']) && empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
		}
		if (empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}
		if (!empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_date_start']) . "') AND DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}

		// get past xx days/month/years requests
		if (!empty($data['filter_time_lap_value']) && !empty($data['filter_time_lap'])) {
			$sql .= " AND r.date_added >= DATE(NOW()) + INTERVAL - ". (int)$data['filter_time_lap_value'] ." ". $this->db->escape($data['filter_time_lap']) ." AND r.date_added <  DATE(NOW()) + INTERVAL 1 DAY ";
		}

		$sql .= " GROUP BY r.mpgdpr_requestlist_id";

		$sort_data = [
			'r.mpgdpr_requestlist_id',
			'r.customer_id',
			'r.email',
			'r.store_id',
			'r.requessttype',
			'r.custom_string',
			'r.server_ip',
			'r.client_ip',
			'r.date_added',
			'r.date_modified',
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY r.mpgdpr_requestlist_id";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
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

	public function getTotalPolicyAcceptances($data) {
		$sql = "SELECT COUNT(DISTINCT r.mpgdpr_policyacceptance_id) AS total FROM " . DB_PREFIX . "mpgdpr_policyacceptance r WHERE r.mpgdpr_policyacceptance_id>0 AND r.status=1";

		if (!empty($data['filter_request_id'])) {
			$sql .= " AND r.mpgdpr_policyacceptance_id = '" . (int)$data['filter_request_id'] . "'";
		}

		if (!empty($data['filter_type'])) {
			$sql .= " AND r.requessttype = '" . $this->db->escape($data['filter_type']) . "'";
		}
		/*13 sep 2019 gdpr session starts*/
		if (!empty($data['filter_email'])) {
			$sql .= " AND (r.customer_id IN (SELECT customer_id FROM " . DB_PREFIX . "customer c WHERE lower(c.email) LIKE '". $this->db->escape(utf8_strtolower($data['filter_email'])) ."%' ) OR lower(r.email) LIKE '". $this->db->escape(utf8_strtolower($data['filter_email'])) ."%' )";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_useragent'])) {
			$sql .= " AND r.user_agent = '" . $this->db->escape($data['filter_useragent']) . "'";
		}
		/*13 sep 2019 gdpr session starts*/
		if (!empty($data['filter_server_ip'])) {
			$sql .= " AND r.server_ip LIKE '%" . $this->db->escape($data['filter_server_ip']) . "%'";
		}
		if (!empty($data['filter_client_ip'])) {
			$sql .= " AND r.client_ip = '%" . $this->db->escape($data['filter_client_ip']) . "%'";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_date_start']) && empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
		}
		if (empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}
		if (!empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_date_start']) . "') AND DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}
		/*13 sep 2019 gdpr session starts*/
		// get past xx days/month/years requests
		if (!empty($data['filter_time_lap_value']) && !empty($data['filter_time_lap'])) {
			$sql .= " AND r.date_added >= DATE(NOW()) + INTERVAL - ". (int)$data['filter_time_lap_value'] ." ". $this->db->escape($data['filter_time_lap']) ." AND r.date_added <  DATE(NOW()) + INTERVAL  1 DAY ";
		}
		/*13 sep 2019 gdpr session ends*/

		$query = $this->db->query($sql);

		return $query->row['total'];
	}
	public function getPolicyAcceptance($mpgdpr_policyacceptance_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "mpgdpr_policyacceptance r WHERE r.mpgdpr_policyacceptance_id='". (int)$mpgdpr_policyacceptance_id ."' AND r.status=1");
		return $query->row;
	}
	public function deletePolicyAcceptance($mpgdpr_policyacceptance_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "mpgdpr_policyacceptance WHERE mpgdpr_policyacceptance_id='". (int)$mpgdpr_policyacceptance_id ."'");
	}
	public function getPolicyAcceptances($data=[]) {

		$sql = "SELECT * FROM " . DB_PREFIX . "mpgdpr_policyacceptance pa WHERE pa.mpgdpr_policyacceptance_id>0 AND pa.status=1";

		if (!empty($data['filter_request_id'])) {
			$sql .= " AND pa.mpgdpr_policyacceptance_id = '" . (int)$data['filter_request_id'] . "'";
		}
		if (!empty($data['filter_type'])) {
			$sql .= " AND pa.requessttype = '" . $this->db->escape($data['filter_type']) . "'";
		}

		/*13 sep 2019 gdpr session starts*/
		if (!empty($data['filter_email'])) {
			$sql .= " AND (pa.customer_id IN (SELECT customer_id FROM " . DB_PREFIX . "customer c WHERE lower(c.email) LIKE '". $this->db->escape(utf8_strtolower($data['filter_email'])) ."%' ) OR lower(pa.email) LIKE '". $this->db->escape(utf8_strtolower($data['filter_email'])) ."%' )";
		}
		/*13 sep 2019 gdpr session ends*/

		if (!empty($data['filter_useragent'])) {
			$sql .= " AND pa.user_agent = '" . $this->db->escape($data['filter_useragent']) . "'";
		}
		/*13 sep 2019 gdpr session starts*/
		if (!empty($data['filter_server_ip'])) {
			$sql .= " AND pa.server_ip LIKE '%" . $this->db->escape($data['filter_server_ip']) . "%'";
		}
		if (!empty($data['filter_client_ip'])) {
			$sql .= " AND pa.client_ip = '%" . $this->db->escape($data['filter_client_ip']) . "%'";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_date_start']) && empty($data['filter_date_end'])) {
			$sql .= " AND DATE(pa.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
		}
		if (empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(pa.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}
		if (!empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(pa.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_date_start']) . "') AND DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}

		// get past xx days/month/years requests
		if (!empty($data['filter_time_lap_value']) && !empty($data['filter_time_lap'])) {
			$sql .= " AND pa.date_added >= DATE(NOW()) + INTERVAL - ". (int)$data['filter_time_lap_value'] ." ". $this->db->escape($data['filter_time_lap']) ." AND pa.date_added <  DATE(NOW()) + INTERVAL 1 DAY ";
		}

		$sql .= " GROUP BY pa.mpgdpr_policyacceptance_id";

		$sort_data = [
			'pa.mpgdpr_policyacceptance_id',
			'pa.requessttype',
			'pa.customer_id',
			'pa.email',
			'pa.store_id',
			'pa.policy_id',
			'pa.server_ip',
			'pa.client_ip',
			'pa.status',
			'pa.date_added',
			'pa.date_modified',
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY pa.mpgdpr_policyacceptance_id";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
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

	public function getRestrictProcessing($customer_id) {
	$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_restrict_processing` WHERE `customer_id`='" . (int)$customer_id . "' ORDER BY date_added DESC LIMIT 0,1");
	$restrict = 0;
	if ($query->row) {
		$restrict = $query->row['status'];
	}
	return $restrict;
  }

  public function getTotalRequestAnonymouses($data) {
		$sql = "SELECT COUNT(DISTINCT r.mpgdpr_deleteme_id) AS total FROM " . DB_PREFIX . "mpgdpr_deleteme r WHERE r.mpgdpr_deleteme_id>0";
		/*13 sep 2019 gdpr session starts*/
		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND r.status = '" . (int)$data['filter_status'] . "'";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_request_id'])) {
			$sql .= " AND r.mpgdpr_deleteme_id = '" . (int)$data['filter_request_id'] . "'";
		}
		if (!empty($data['filter_date_deletion'])) {
			$sql .= " AND DATE(r.date_deletion) = DATE('" . $this->db->escape($data['filter_date_deletion']) . "')";
		}
		if (!empty($data['filter_email'])) {
			$sql .= " AND r.customer_id IN (SELECT customer_id FROM " . DB_PREFIX . "customer c WHERE lower(c.email) LIKE '". $this->db->escape(utf8_strtolower($data['filter_email'])) ."%' )";
		}
		if (!empty($data['filter_useragent'])) {
			$sql .= " AND r.user_agent = '" . $this->db->escape($data['filter_useragent']) . "'";
		}
		/*13 sep 2019 gdpr session starts*/
		if (!empty($data['filter_server_ip'])) {
			$sql .= " AND r.server_ip LIKE '%" . $this->db->escape($data['filter_server_ip']) . "%'";
		}
		if (!empty($data['filter_client_ip'])) {
			$sql .= " AND r.client_ip = '%" . $this->db->escape($data['filter_client_ip']) . "%'";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_date_start']) && empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
		}
		if (empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}
		if (!empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_date_start']) . "') AND DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}
		/*13 sep 2019 gdpr session starts*/
		// get past xx days/month/years requests
		if (!empty($data['filter_time_lap_value']) && !empty($data['filter_time_lap'])) {
			$sql .= " AND r.date_added >= DATE(NOW()) + INTERVAL - ". (int)$data['filter_time_lap_value'] ." ". $this->db->escape($data['filter_time_lap']) ." AND r.date_added <  DATE(NOW()) + INTERVAL  1 DAY ";
		}
		/*13 sep 2019 gdpr session ends*/
		$query = $this->db->query($sql);

		return $query->row['total'];
	}
	public function getRequestAnonymouses($data) {

		$sql = "SELECT * FROM " . DB_PREFIX . "mpgdpr_deleteme d WHERE d.mpgdpr_deleteme_id>0";
		/*13 sep 2019 gdpr session starts*/
		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND d.status = '" . (int)$data['filter_status'] . "'";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_request_id'])) {
			$sql .= " AND d.mpgdpr_deleteme_id = '" . (int)$data['filter_request_id'] . "'";
		}
		if (!empty($data['filter_date_deletion'])) {
			$sql .= " AND DATE(d.date_deletion) = DATE('" . $this->db->escape($data['filter_date_deletion']) . "')";
		}
		if (!empty($data['filter_email'])) {
			$sql .= " AND d.customer_id IN (SELECT customer_id FROM " . DB_PREFIX . "customer c WHERE lower(c.email) LIKE '". $this->db->escape(utf8_strtolower($data['filter_email'])) ."%' )";
		}
		if (!empty($data['filter_useragent'])) {
			$sql .= " AND d.user_agent = '" . $this->db->escape($data['filter_useragent']) . "'";
		}
		/*13 sep 2019 gdpr session starts*/
		if (!empty($data['filter_server_ip'])) {
			$sql .= " AND d.server_ip LIKE '%" . $this->db->escape($data['filter_server_ip']) . "%'";
		}
		if (!empty($data['filter_client_ip'])) {
			$sql .= " AND d.client_ip = '%" . $this->db->escape($data['filter_client_ip']) . "%'";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_date_start']) && empty($data['filter_date_end'])) {
			$sql .= " AND DATE(d.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
		}
		if (empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(d.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}
		if (!empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(d.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_date_start']) . "') AND DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}

		// get past xx days/month/years requests
		if (!empty($data['filter_time_lap_value']) && !empty($data['filter_time_lap'])) {
			$sql .= " AND d.date_added >= DATE(NOW()) + INTERVAL - ". (int)$data['filter_time_lap_value'] ." ". $this->db->escape($data['filter_time_lap']) ." AND d.date_added <  DATE(NOW()) + INTERVAL 1 DAY ";
		}

		$sql .= " GROUP BY d.mpgdpr_deleteme_id";

		$sort_data = [
			'd.mpgdpr_deleteme_id',
			'd.customer_id',
			'd.email',
			'd.store_id',
			'd.server_ip',
			'd.client_ip',
			'd.status',
			'd.code',
			'd.date_deletion',
			'd.date_added',
			'd.expire_on',
			'd.date_modified',
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY d.mpgdpr_deleteme_id";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
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

	public function getRequestAnonymouse($mpgdpr_deleteme_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_deleteme` WHERE `mpgdpr_deleteme_id`='" . (int)$mpgdpr_deleteme_id . "'");
		return $query->row;
	}

	public function updateRequestAnonymouseAndAnonymouse($data) {
		if (empty($data['date'])) {
			$date = date('Y-m-d H:i:s');
		} else {
			$date = $data['date'];
		}
		$this->db->query("UPDATE `" . DB_PREFIX . "mpgdpr_deleteme` SET `status`='" . \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_COMPLETE . "', `date_deletion`='" . $this->db->escape($data['date_deletion']) . "', `date_modified`='". $this->db->escape($date) ."' WHERE `mpgdpr_deleteme_id`='" . (int)$data['mpgdpr_deleteme_id'] . "'");

		// anonymouse customer data
		$mpgdpr_deleteme_info = $this->getRequestAnonymouse($data['mpgdpr_deleteme_id']);
		if ($mpgdpr_deleteme_info) {
			$model_customer = $this->mpgdpr->getAdminCustomerModelString();
			$customer_info = $this->{$model_customer}->getCustomer($mpgdpr_deleteme_info['customer_id']);
			if ($customer_info) {

				$this->mpgdpr->log("Request anonymouse start");
				$this->mpgdpr->log("Customer information firstname: {$customer_info['firstname']}, lastname: {$customer_info['firstname']}, customer_id: {$customer_info['customer_id']} ");

				$curl = curl_init();

				// Set SSL if required
				if (substr(HTTPS_CATALOG, 0, 5) == 'https') {
					$this->mpgdpr->log("SSL");
					curl_setopt($curl, CURLOPT_PORT, 443);
				} else {
					$this->mpgdpr->log("Not SSL");
				}

				$rand = [];

				do {
					for($i=0;$i<mt_rand(0,10);$i++) {
						$rand[] = mt_rand(0,15);
					}
				} while (empty($rand));

				// echo "\n";
				// echo "rand data";
				// echo "\n\n";
				// print_r($rand);
				// $this->mpgdpr->log("Rand Data " . print_r($rand, 1));

				$key = array_rand($rand);
				$value = array_rand($rand);

				// echo "\n";
				// echo "key.value";
				// echo "\n\n";
				// print_r($key .' . ' . $value);
				// $this->mpgdpr->log("key.value " . print_r($key .' . ' . $value, 1));

				$filename = 'mpgdpr.'. $mpgdpr_deleteme_info['customer_id'] .'.json.'.$rand[$key].'.'.$rand[$value].$rand[$key] . '';

				$fjson = [];
				$fjson['customer_id'] = ($mpgdpr_deleteme_info['customer_id']);
				$fjson['key'] = ($key);
				$fjson['value'] = ($value);
				$fjson['filename'] = ($filename);

				$string = json_encode($fjson) ;

				$handle = fopen(DIR_CACHE . $filename, 'w');
				fwrite($handle, $string);
				fclose($handle);



				$api_info = [];
				$api_info['key'] = $fjson['key'];
				$api_info['value'] = $fjson['value'];
				$api_info['filename'] = $fjson['filename'];
				$api_info['token'] = $this->session->data[$this->mpgdpr->token];
				// echo "\n";
				// echo "file name : " . DIR_CACHE . $filename;

				// $this->mpgdpr->log("filename: " . print_r($filename, 1));

				// echo "\n";
				// echo "FJSON DATA";
				// echo "\n\n";
				// print_r($fjson);

				// echo "\n";
				// echo "api_info DATA";
				// echo "\n\n";
				// print_r($api_info);
				$this->mpgdpr->log("api_info DATA " . print_r($api_info, 1));


				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_HTTPHEADER, [
					'X-OC-MPGDPR:1',
					'X-OC-MPGDPR-FRONT:'. $this->session->data[$this->mpgdpr->token],
				]);
				// Which lead me to add code to my cURL request that essentially disables the SSL verification.
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

				// curl_setopt($curl, CURLINFO_HEADER_OUT, false);
				// curl_setopt($curl, CURLOPT_USERAGENT, $this->request->server['HTTP_USER_AGENT']);
				// curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				// curl_setopt($curl, CURLOPT_FORBID_REUSE, false);

				curl_setopt($curl, CURLINFO_HEADER_OUT, false);
				curl_setopt($curl, CURLOPT_USERAGENT, $this->request->server['HTTP_USER_AGENT']);

				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
				// CURLOPT_FORBID_REUSE was false, which result in curl not works
				curl_setopt($curl, CURLOPT_FORBID_REUSE, false);


				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_URL, HTTPS_CATALOG . 'index.php?route=extension/mpgdpr/api_login/ignit');
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($api_info));
				$json = curl_exec($curl);


				$response = json_decode($json, true);
				// echo "\n";
				// echo "CURL GET INFO";
				// echo "\n\n";
				// print_r(curl_getinfo($curl));

				curl_close($curl);
				// echo "\n";
				// echo "CURL CALL URL :" . HTTPS_CATALOG . 'index.php?route=extension/mpgdpr/api_login/ignit';
				// echo "\n\n";

				// $this->mpgdpr->log("CURL CALL URL : " . HTTPS_CATALOG . 'index.php?route=extension/mpgdpr/api_login/ignit');

				// echo "\n";
				// echo "json data";
				// echo "\n\n";
				// print_r($json);

				$this->mpgdpr->log("Curl return data : " . print_r($json, 1));

				// echo "\n";
				// echo "response";
				// echo "\n\n";
				// print_r($response);

				$this->mpgdpr->log("Curl response data : " . print_r($response, 1));

				// echo "\n";
				// echo "FILE TO UNLINK : ";
				// echo DIR_CACHE . $filename;

				$this->mpgdpr->log("file to unlink : " . DIR_CACHE . $filename);

				// @unlink(DIR_CACHE . $filename);

				if (
					(isset($response['filename']) && $response['filename'] == $filename) &&
					(isset($response['success']) && $response['success'] == 'truefalse') &&
					(isset($response['token']) && $response['token'] == $this->session->data[$this->mpgdpr->token])

				) {
					// send last email to customer for anonymouse data by admin.
					// 01-05-2022: updation start
			$mail_user = $this->config->get('mpgdpr_mail_user');
			/*
			 * subject : ''
			 * msg : ''
			 */
			$email_template_user = [];
			$emailtemplate = $this->config->get('mpgdpr_emailtemplate');
			if (isset($emailtemplate['aarpb'])) {
				if (isset($emailtemplate['aarpb']['user'][ (int)$this->config->get('config_language_id') ])) {
					$email_template_user = $emailtemplate['aarpb']['user'][ (int)$this->config->get('config_language_id') ];
				}
			}

					$store_info = $this->storeInfo();
					$this->load->language('mpgdpr/mail');
					if (!isset($mail_user['aarpb']) || (isset($mail_user['aarpb']) && $mail_user['aarpb'] == 1)) {
						$find = [
							// 01-05-2022: updation start
				  '{user_email}',
				  '{store_name}',
				  '{store_link}',
				  '{store_logo}',
				  // 01-05-2022: updation end
						];
						$replace = [
							// 01-05-2022: updation start
				  'user_email' => $customer_info['email'],
				  'store_name' => $store_info['name'],
				  'store_link' => $store_info['href'],
				  'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
				  // 01-05-2022: updation end
						];

						$mail_subject = $this->language->get('text_requestanonymouse_complete_customer_subject');
						$mail_message = $this->language->get('text_requestanonymouse_complete_customer_message');

						// 01-05-2022: updation start
			  if (!empty($email_template_user['subject'])) {
				  $mail_subject = $email_template_user['subject'];
			  }
			  if (!empty($email_template_user['msg'])) {
				  $mail_message = $email_template_user['msg'];
			  }
			  // 01-05-2022: updation end

						$subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

						$message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));

						$mail = $this->mpgdpr->getMailObject();

						$mail->setTo($customer_info['email']);

					$mail->setFrom($this->config->get('config_email'));
					$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
					$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
					$mail->setReplyTo($this->config->get('config_email'));
					$mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
					// echo "\n";
					// echo "MAIL DATA";
					// echo "\n\n";
					// print_r($mail);
					$this->mpgdpr->log("One last email from admin to customer. customer_id: {". $customer_info['customer_id'] ."}, email: {". $customer_info['email'] ."}");
					// $this->mpgdpr->log("One last email from admin to customer. customer_id: {". $customer_info['customer_id'] ."} : " . print_r($mail, 1));
					$mail->send();
					}
					// 01-05-2022: updation start
				}
			}
		}

		// die;
	}

	public function updateRequestAnonymouseAndDeny($data) {
		if (empty($data['date'])) {
			$date = date('Y-m-d H:i:s');
		} else {
			$date = $data['date'];
		}
		$this->db->query("UPDATE `" . DB_PREFIX . "mpgdpr_deleteme` SET `status`='" . \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_DENY . "', `denyreason`='" . $this->db->escape($data['denyreason']) . "', `date_deletion`='" . $this->db->escape($data['date_deletion']) . "', `date_modified`='". $this->db->escape($date) ."' WHERE `mpgdpr_deleteme_id`='" . (int)$data['mpgdpr_deleteme_id'] . "'");

		$mpgdpr_deleteme_info = $this->getRequestAnonymouse($data['mpgdpr_deleteme_id']);
		if ($mpgdpr_deleteme_info) {
			$model_customer = $this->mpgdpr->getAdminCustomerModelString();
			$customer_info = $this->{$model_customer}->getCustomer($mpgdpr_deleteme_info['customer_id']);
			if ($customer_info) {
				// send email to customer of deny request with denyreason
				// 01-05-2022: updation start
		$mail_user = $this->config->get('mpgdpr_mail_user');
		/*
		 * subject : ''
		 * msg : ''
		 */
		$email_template_user = [];
		$emailtemplate = $this->config->get('mpgdpr_emailtemplate');
		if (isset($emailtemplate['aard'])) {
			if (isset($emailtemplate['aard']['user'][ (int)$this->config->get('config_language_id') ])) {
				$email_template_user = $emailtemplate['aard']['user'][ (int)$this->config->get('config_language_id') ];
			}
		}

				$store_info = $this->storeInfo();
				$this->load->language('mpgdpr/mail');
				if (!isset($mail_user['aard']) || (isset($mail_user['aard']) && $mail_user['aard'] == 1)) {
					// 06-09-2019 fix
					$find = [
						'{denyreason}',
						// 01-05-2022: updation start
			  '{user_email}',
			  '{store_name}',
			  '{store_link}',
			  '{store_logo}',
			  // 01-05-2022: updation end
					];

					$replace = [
						'denyreason' => nl2br($data['denyreason']),
						// 01-05-2022: updation start
			  'user_email' => $customer_info['email'],
			  'store_name' => $store_info['name'],
			  'store_link' => $store_info['href'],
			  'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
			  // 01-05-2022: updation end
					];
					// 06-09-2019 fix

					$mail_subject = $this->language->get('text_requestanonymouse_deny_customer_subject');
					$mail_message = $this->language->get('text_requestanonymouse_deny_customer_message');

					// 01-05-2022: updation start
			if (!empty($email_template_user['subject'])) {
				$mail_subject = $email_template_user['subject'];
			}
			if (!empty($email_template_user['msg'])) {
				$mail_message = $email_template_user['msg'];
			}
			// 01-05-2022: updation end

					$subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

					$message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));

					$mail = $this->mpgdpr->getMailObject();

					$mail->setTo($customer_info['email']);

			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
			$mail->setReplyTo($this->config->get('config_email'));
			$mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
			$mail->send();
				}
				// 01-05-2022: updation end
			}
		}
	}
	// 01-05-2022: updation start
	public function updateRequestAnonymouseStatus($mpgdpr_deleteme_id, $status) {
		$this->db->query("UPDATE " . DB_PREFIX . "mpgdpr_deleteme SET status='". (int)$status ."' WHERE mpgdpr_deleteme_id='". (int)$mpgdpr_deleteme_id ."'");
	}
	public function updateRequestAccessDataStatus($mpgdpr_datarequest_id, $status) {
		$this->db->query("UPDATE " . DB_PREFIX . "mpgdpr_datarequest SET status='". (int)$status ."' WHERE mpgdpr_datarequest_id='". (int)$mpgdpr_datarequest_id ."'");
	}
	// 01-05-2022: updation end
	public function getTotalRequestAccessDatas($data) {
		$sql = "SELECT COUNT(DISTINCT r.mpgdpr_datarequest_id) AS total FROM " . DB_PREFIX . "mpgdpr_datarequest r WHERE r.mpgdpr_datarequest_id>0";
		/*13 sep 2019 gdpr session starts*/
		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND r.status = '" . (int)$data['filter_status'] . "'";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_request_id'])) {
			$sql .= " AND r.mpgdpr_datarequest_id = '" . (int)$data['filter_request_id'] . "'";
		}
		/*13 sep 2019 gdpr session starts*/
		if (!empty($data['filter_date_send'])) {
			$sql .= " AND DATE(r.date_send) = DATE('" . $this->db->escape($data['filter_date_send']) . "')";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_email'])) {
			// 01-05-2022: updation start
			$sql .= " AND (r.email LIEK '". $this->db->escape(utf8_strtolower($data['filter_email'])) ."%' OR r.customer_id IN (SELECT customer_id FROM " . DB_PREFIX . "customer c WHERE lower(c.email) LIKE '". $this->db->escape(utf8_strtolower($data['filter_email'])) ."%' ))";
			// 01-05-2022: updation end
		}
		if (!empty($data['filter_useragent'])) {
			$sql .= " AND r.user_agent = '" . $this->db->escape($data['filter_useragent']) . "'";
		}
		/*13 sep 2019 gdpr session starts*/
		if (!empty($data['filter_server_ip'])) {
			$sql .= " AND r.server_ip LIKE '%" . $this->db->escape($data['filter_server_ip']) . "%'";
		}
		if (!empty($data['filter_client_ip'])) {
			$sql .= " AND r.client_ip = '%" . $this->db->escape($data['filter_client_ip']) . "%'";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_date_start']) && empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
		}
		if (empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}
		if (!empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(r.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_date_start']) . "') AND DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}
		/*13 sep 2019 gdpr session starts*/
		// get past xx days/month/years requests
		if (!empty($data['filter_time_lap_value']) && !empty($data['filter_time_lap'])) {
			$sql .= " AND r.date_added >= DATE(NOW()) + INTERVAL - ". (int)$data['filter_time_lap_value'] ." ". $this->db->escape($data['filter_time_lap']) ." AND r.date_added <  DATE(NOW()) + INTERVAL  1 DAY ";
		}
		/*13 sep 2019 gdpr session ends*/
		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function deleteRequestAccessData($mpgdpr_datarequest_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "mpgdpr_datarequest WHERE mpgdpr_datarequest_id='". (int)$mpgdpr_datarequest_id ."'");
	}


	public function getRequestAccessDatas($data) {

		$sql = "SELECT * FROM " . DB_PREFIX . "mpgdpr_datarequest dr WHERE dr.mpgdpr_datarequest_id>0";
		/*13 sep 2019 gdpr session starts*/
		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND dr.status = '" . (int)$data['filter_status'] . "'";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_request_id'])) {
			$sql .= " AND dr.mpgdpr_datarequest_id = '" . (int)$data['filter_request_id'] . "'";
		}
		/*13 sep 2019 gdpr session starts*/
		if (!empty($data['filter_date_send'])) {
			$sql .= " AND DATE(dr.date_send) = DATE('" . $this->db->escape($data['filter_date_send']) . "')";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_email'])) {
			// 01-05-2022: updation start
			$sql .= " AND (dr.email LIEK '". $this->db->escape(utf8_strtolower($data['filter_email'])) ."%' OR dr.customer_id IN (SELECT customer_id FROM " . DB_PREFIX . "customer c WHERE lower(c.email) LIKE '". $this->db->escape(utf8_strtolower($data['filter_email'])) ."%' ))";
			// 01-05-2022: updation end
		}
		if (!empty($data['filter_useragent'])) {
			$sql .= " AND dr.user_agent = '" . $this->db->escape($data['filter_useragent']) . "'";
		}
		/*13 sep 2019 gdpr session starts*/
		if (!empty($data['filter_server_ip'])) {
			$sql .= " AND dr.server_ip LIKE '%" . $this->db->escape($data['filter_server_ip']) . "%'";
		}
		if (!empty($data['filter_client_ip'])) {
			$sql .= " AND dr.client_ip = '%" . $this->db->escape($data['filter_client_ip']) . "%'";
		}
		/*13 sep 2019 gdpr session ends*/
		if (!empty($data['filter_date_start']) && empty($data['filter_date_end'])) {
			$sql .= " AND DATE(dr.date_added) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
		}
		if (empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(dr.date_added) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}
		if (!empty($data['filter_date_start']) && !empty($data['filter_date_end'])) {
			$sql .= " AND DATE(dr.date_added) BETWEEN DATE('" . $this->db->escape($data['filter_date_start']) . "') AND DATE('" . $this->db->escape($data['filter_date_end']) . "')";
		}

		// get past xx days/month/years requests
		if (!empty($data['filter_time_lap_value']) && !empty($data['filter_time_lap'])) {
			$sql .= " AND dr.date_added >= DATE(NOW()) + INTERVAL - ". (int)$data['filter_time_lap_value'] ." ". $this->db->escape($data['filter_time_lap']) ." AND dr.date_added <  DATE(NOW()) + INTERVAL 1 DAY ";
		}

		$sql .= " GROUP BY dr.mpgdpr_datarequest_id";

		$sort_data = [
			'dr.mpgdpr_datarequest_id',
			'dr.customer_id',
			'dr.email',
			'dr.store_id',
			'dr.server_ip',
			'dr.client_ip',
			'dr.status',
			'dr.date_send',
			'dr.date_added',
			'dr.expire_on',
			'dr.date_modified',
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY dr.mpgdpr_datarequest_id";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
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

	public function getRequestAccessData($mpgdpr_datarequest_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_datarequest` WHERE `mpgdpr_datarequest_id`='" . (int)$mpgdpr_datarequest_id . "'");
		return $query->row;
	}

	public function updateRequestAccessDataAndSendReport($data) {
		if (empty($data['date'])) {
			$date = date('Y-m-d H:i:s');
		} else {
			$date = $data['date'];
		}
		$this->db->query("UPDATE `" . DB_PREFIX . "mpgdpr_datarequest` SET `status`='" . \Mpgdpr\Mpgdpr :: REQUESTACCESS_REPORTSEND . "', `date_send`='" . $this->db->escape($data['date_send']) . "', `attachment`='" . $this->db->escape($data['attachment']) . "', `date_modified`='" . $this->db->escape($date) . "' WHERE `mpgdpr_datarequest_id`='" . (int)$data['mpgdpr_datarequest_id'] . "'");

		$this->updateUploadInUseByCode($data['attachment']);


		$mpgdpr_datarequest_info = $this->getRequestAccessData($data['mpgdpr_datarequest_id']);
		if ($mpgdpr_datarequest_info) {
			$model_customer = $this->mpgdpr->getAdminCustomerModelString();
			$customer_info = $this->{$model_customer}->getCustomer($mpgdpr_datarequest_info['customer_id']);
			if ($customer_info) {
				// send email to customer with attachment of the file uploaded

				// gdpr_accessdata reports folder
				$dir = 'mpgdpr_accessdata/';
				$dircopy = 'mpgdpr_accessdata/copy/';

				$this->mpgdpr->mkdir(DIR_UPLOAD . $dir);
				$this->mpgdpr->mkdir(DIR_UPLOAD . $dircopy);

				// 01-05-2022: updation start
		$mail_user = $this->config->get('mpgdpr_mail_user');
		/*
		 * subject : ''
		 * msg : ''
		 */
		$email_template_user = [];
		$emailtemplate = $this->config->get('mpgdpr_emailtemplate');
		if (isset($emailtemplate['aapdsr'])) {
			if (isset($emailtemplate['aapdsr']['user'][ (int)$this->config->get('config_language_id') ])) {
				$email_template_user = $emailtemplate['aapdsr']['user'][ (int)$this->config->get('config_language_id') ];
			}
		}
				$store_info = $this->storeInfo();
				$this->load->language('mpgdpr/mail');
				if (!isset($mail_user['aapdsr']) || (isset($mail_user['aapdsr']) && $mail_user['aapdsr'] == 1)) {
					$find = [
						// 01-05-2022: updation start
			  '{user_email}',
			  '{store_name}',
			  '{store_link}',
			  '{store_logo}',
			  // 01-05-2022: updation end
					];
					$replace = [
						// 01-05-2022: updation start
			  'user_email' => $customer_info['email'],
			  'store_name' => $store_info['name'],
			  'store_link' => $store_info['href'],
			  'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
			  // 01-05-2022: updation end
					];

					$mail_subject = $this->language->get('text_datarequest_sendreport_customer_subject');
					$mail_message = $this->language->get('text_datarequest_sendreport_customer_message');

					// 01-05-2022: updation start
			if (!empty($email_template_user['subject'])) {
				$mail_subject = $email_template_user['subject'];
			}
			if (!empty($email_template_user['msg'])) {
				$mail_message = $email_template_user['msg'];
			}
			// 01-05-2022: updation end

					$subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

					$message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));

					$filecopy = '';
					$mail = $this->mpgdpr->getMailObject();

					$upload_info = $this->getUploadByCode($mpgdpr_datarequest_info['attachment']);
					if ($upload_info) {
						$file = DIR_UPLOAD. $dir . $upload_info['filename'];
						$filecopy = DIR_UPLOAD. $dircopy . $upload_info['name'];
						$mask = basename($upload_info['name']);

						if (file_exists($file)) {
							// copy file
							copy($file, $filecopy);
							// 06-09-2019 fix add original file instead copy file
							$mail->addAttachment($filecopy);
							// 06-09-2019 fix
						}
					}

					$mail->setTo($customer_info['email']);

			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
			$mail->setReplyTo($this->config->get('config_email'));
			$mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
			$mail->send();
			// unlink the copy file.
			if (file_exists($filecopy)) {
				@unlink($filecopy);
			}
				}
				// 01-05-2022: updation end
			}
		}
	}
	public function updateRequestAccessDataAndDeny($data) {
		if (empty($data['date'])) {
			$date = date('Y-m-d H:i:s');
		} else {
			$date = $data['date'];
		}
		$this->db->query("UPDATE `" . DB_PREFIX . "mpgdpr_datarequest` SET `status`='" . \Mpgdpr\Mpgdpr :: REQUESTACCESS_DENY . "', `denyreason`='" . $this->db->escape($data['denyreason']) . "', `date_modified`='" . $this->db->escape($date) . "' WHERE `mpgdpr_datarequest_id`='" . (int)$data['mpgdpr_datarequest_id'] . "'");

		$mpgdpr_datarequest_info = $this->getRequestAccessData($data['mpgdpr_datarequest_id']);
		if ($mpgdpr_datarequest_info) {
			$model_customer = $this->mpgdpr->getAdminCustomerModelString();
			$customer_info = $this->{$model_customer}->getCustomer($mpgdpr_datarequest_info['customer_id']);
			if ($customer_info) {
				// send email to deny request with denyreason
				// 01-05-2022: updation start
		$mail_user = $this->config->get('mpgdpr_mail_user');
		/*
		 * subject : ''
		 * msg : ''
		 */
		$email_template_user = [];
		$emailtemplate = $this->config->get('mpgdpr_emailtemplate');
		if (isset($emailtemplate['aapdd'])) {
			if (isset($emailtemplate['aapdd']['user'][ (int)$this->config->get('config_language_id') ])) {
				$email_template_user = $emailtemplate['aapdd']['user'][ (int)$this->config->get('config_language_id') ];
			}
		}

		$store_info = $this->storeInfo();

				$this->load->language('mpgdpr/mail');

				if (!isset($mail_user['aapdd']) || (isset($mail_user['aapdd']) && $mail_user['aapdd'] == 1)) {
					$find = [
						'{denyreason}',
						// 01-05-2022: updation start
			  '{user_email}',
			  '{store_name}',
			  '{store_link}',
			  '{store_logo}',
			  // 01-05-2022: updation end
					];
					$replace = [
						// 06-09-2019 fix
						'denyreason' => nl2br($data['denyreason']),
						// 06-09-2019 fix
						// 01-05-2022: updation start
			  'user_email' => $customer_info['email'],
			  'store_name' => $store_info['name'],
			  'store_link' => $store_info['href'],
			  'store_logo' => $store_info['logo'] ? '<img src="'. $store_info['logo'] .'">' : '',
			  // 01-05-2022: updation end
					];

					$mail_subject = $this->language->get('text_datarequest_deny_customer_subject');
					$mail_message = $this->language->get('text_datarequest_deny_customer_message');

					// 01-05-2022: updation start
		  if (!empty($email_template_user['subject'])) {
			  $mail_subject = $email_template_user['subject'];
		  }
		  if (!empty($email_template_user['msg'])) {
			  $mail_message = $email_template_user['msg'];
		  }
		  // 01-05-2022: updation end

					$subject = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_subject))));

					$message = str_replace(["\r\n", "\r", "\n"], '', preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '', trim(str_replace($find, $replace, $mail_message))));

					$mail = $this->mpgdpr->getMailObject();

					$mail->setTo($customer_info['email']);

			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
			$mail->setReplyTo($this->config->get('config_email'));
			$mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
			$mail->send();
				}
				// 01-05-2022: updation end
			}
		}
	}
	// 01-05-2022: updation start
	private function storeInfo($store_id=0) {
		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		$logo = '';
		if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$logo = $server . 'image/' . $this->config->get('config_logo');
		}

		return [
			'logo' => $logo,
			'name' => $this->config->get('config_name'),
			'href' => $this->url->link('common/home', '', true),
		];
	}
	// 01-05-2022: updation end
	public function addUpload($name, $filename) {
		$code = sha1(uniqid(mt_rand(), true));

		$this->db->query("INSERT INTO `" . DB_PREFIX . "mpgdpr_upload` SET `name`='" . $this->db->escape($name) . "', `filename`='" . $this->db->escape($filename) . "', `code`='" . $this->db->escape($code) . "', `date_added` = NOW()");

		return $code;
	}

	public function getUploadByCode($code) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "mpgdpr_upload` WHERE code = '" . $this->db->escape($code) . "'");
		return $query->row;
	}
	public function updateUploadInUseByCode($code) {
		$this->db->query("UPDATE `" . DB_PREFIX . "mpgdpr_upload` SET in_use=1 WHERE code = '" . $this->db->escape($code) . "'");
	}
	// 01-05-2022: updation start
	public function getInformation($information_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (id.information_id=i.information_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND  i.information_id = '" . (int)$information_id . "'");
		return $query->row;
	}
	// 01-05-2022: updation end
}
