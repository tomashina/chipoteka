<?php
class ControllerExtensionMpGdprRequestAccessData extends \Mpgdpr\Controller {
	use \Mpgdpr\trait_mpgdpr;
	private $error = [];

	public function __construct($registry) {
		parent :: __construct($registry);
		$this->igniteTraitMpGdpr($registry);
	}

	public function delete() {
		$this->load->language($this->extension_path . 'mpgdpr/requestaccessdata');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $mpgdpr_datarequest_id) {
				$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->deleteRequestAccessData($mpgdpr_datarequest_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->filters();

			$this->response->redirect($this->url->link($this->extension_path . 'mpgdpr/requestaccessdata', $this->token.'=' . $this->session->data[$this->token] . $url, true));
		}

		$this->index();
	}

	public function index() {
		$this->load->language($this->extension_path . 'mpgdpr/requestaccessdata');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addStyle('view/stylesheet/mpgdpr/mpgdpr.css');

		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

		if (isset($this->request->get['filter_status'])) {
			$filter_status = $this->request->get['filter_status'];
		} else {
			$filter_status = null;
		}

		if (isset($this->request->get['filter_request_id'])) {
			$filter_request_id = $this->request->get['filter_request_id'];
		} else {
			$filter_request_id = null;
		}

		if (isset($this->request->get['filter_email'])) {
			$filter_email = $this->request->get['filter_email'];
		} else {
			$filter_email = null;
		}

		if (isset($this->request->get['filter_date_send'])) {
			$filter_date_send = $this->request->get['filter_date_send'];
		} else {
			$filter_date_send = null;
		}
	

		if (isset($this->request->get['filter_date_start'])) {
			$filter_date_start = $this->request->get['filter_date_start'];
		} else {
			$filter_date_start = null;
		}

		if (isset($this->request->get['filter_date_end'])) {
			$filter_date_end = $this->request->get['filter_date_end'];
		} else {
			$filter_date_end = null;
		}

		if (isset($this->request->get['filter_time_lap_value'])) {
			$filter_time_lap_value = $this->request->get['filter_time_lap_value'];
		} else {
			$filter_time_lap_value = null;
		}

		if (isset($this->request->get['filter_time_lap'])) {
			$filter_time_lap = $this->request->get['filter_time_lap'];
		} else {
			$filter_time_lap = null;
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'r.date_added';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}


		$url = $this->filters();

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->token.'=' . $this->session->data[$this->token], true)
		];

		$this->breadcrumbs($data);

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->extension_path . 'mpgdpr/requestaccessdata', $this->token.'=' . $this->session->data[$this->token] . $url, true)
		];

		if (VERSION < '3.0.0.0') {
			$this->getAllLanguageMpgdpr($data);
		}

		$data['token'] = $this->session->data[$this->token];
		$data['get_token'] = $this->token;
		$data['extension_path'] = $this->extension_path;

		$data['delete'] = $this->url->link($this->extension_path . 'mpgdpr/requestaccessdata/delete', $this->token.'=' . $this->session->data[$this->token] . $url, true);

		$data['requests'] = [];

		$filter_data = [
			'sort'  => $sort,
			'order' => $order,
			'filter_status' => $filter_status,
			'filter_request_id' => $filter_request_id,
			'filter_email' => $filter_email,
			'filter_date_send' => $filter_date_send,
			'filter_date_start' => $filter_date_start,
			'filter_date_end' => $filter_date_end,
			'filter_time_lap_value' => $filter_time_lap_value,
			'filter_time_lap' => $filter_time_lap,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		];

		$requestaccessdata_total = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getTotalRequestAccessDatas($filter_data);

		$results = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getRequestAccessDatas($filter_data);

		// 01-05-2022: updation start
		$today = strtotime(date('Y-m-d H:i:s'));
		// 01-05-2022: updation end

		$customer_model = $this->customerModelObj();

		// enable action buttons only when request is confirmed
		// 01-05-2022: updation start
		$data['requestaccess_confirmed'] = \Mpgdpr\Mpgdpr :: REQUESTACCESS_CONFIRMED;
		$data['requestaccess_awating'] = \Mpgdpr\Mpgdpr :: REQUESTACCESS_AWATING;
		// 01-05-2022: updation end
		foreach ($results as $result) {
			// 01-05-2022: updation start
			$email = $result['email'];
			if (empty($email)) {
				$customer_info = $this->{$customer_model}->getCustomer($result['customer_id']);
				if ($customer_info) {
					$email = $customer_info['email'];
				}
			}
			// 01-05-2022: updation end
			$status_text = '';
			// 01-05-2022: updation start
			if ($result['status']==\Mpgdpr\Mpgdpr :: REQUESTACCESS_EXPIRE) {
				$status_text = $this->language->get('text_expire');
			} elseif ($result['status']==\Mpgdpr\Mpgdpr :: REQUESTACCESS_CONFIRMED) {
				$status_text = $this->language->get('text_confirmed');
			} elseif ($result['status']==\Mpgdpr\Mpgdpr :: REQUESTACCESS_AWATING) {
				$status_text = $this->language->get('text_awating');
			} elseif ($result['status']==\Mpgdpr\Mpgdpr :: REQUESTACCESS_REPORTSEND) {
				$status_text = $this->language->get('text_reportsend');
			} elseif ($result['status']==\Mpgdpr\Mpgdpr :: REQUESTACCESS_DENY) {
				$status_text = $this->language->get('text_deny');
			}

			$approve = '';
			if ($result['status'] == \Mpgdpr\Mpgdpr :: REQUESTACCESS_AWATING /*&& !($today > strtotime($result['expire_on']))*/) {
				$approve = $this->url->link($this->extension_path . 'mpgdpr/requestaccessdata/approve', $this->token.'=' . $this->session->data[$this->token] . '&mpgdpr_datarequest_id=' . $result['mpgdpr_datarequest_id'] . $url, true);
			}
			// 01-05-2022: updation end

			$data['requests'][] = [
				'mpgdpr_datarequest_id' => $result['mpgdpr_datarequest_id'],
				'email'        => $email,
				'date_send'        => $result['date_send'] != '0000-00-00' ? $result['date_send'] : '',
				'status_text'        => $status_text,
				'status'        => $result['status'],
				'date_added'        => $result['date_added'],
				// 01-05-2022: updation end
				'expire_on'        => $result['expire_on'],
				'date_modified'        => $result['date_modified'] != '0000-00-00 00:00:00' ? $result['date_modified'] : '',
				'expire_on'        => $result['expire_on'],
				'expire'        => ($today > strtotime($result['expire_on'])),
				'approve'        => $approve,
				// 01-05-2022: updation end
			];
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = [];
		}

		$url = $this->filters(array('sort', 'order'));

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		$data['sort_datarequest_id'] = $this->url->link($this->extension_path . 'mpgdpr/requestaccessdata', $this->token.'=' . $this->session->data[$this->token] . '&sort=dr.mpgdpr_datarequest_id' . $url, true);
		$data['sort_email'] = $this->url->link($this->extension_path . 'mpgdpr/requestaccessdata', $this->token.'=' . $this->session->data[$this->token] . '&sort=dr.email' . $url, true);
		$data['sort_status'] = $this->url->link($this->extension_path . 'mpgdpr/requestaccessdata', $this->token.'=' . $this->session->data[$this->token] . '&sort=dr.status' . $url, true);
		$data['sort_date_send'] = $this->url->link($this->extension_path . 'mpgdpr/requestaccessdata', $this->token.'=' . $this->session->data[$this->token] . '&sort=dr.date_send' . $url, true);
		$data['sort_date_added'] = $this->url->link($this->extension_path . 'mpgdpr/requestaccessdata', $this->token.'=' . $this->session->data[$this->token] . '&sort=dr.date_added' . $url, true);
		$data['sort_expire_on'] = $this->url->link($this->extension_path . 'mpgdpr/requestaccessdata', $this->token.'=' . $this->session->data[$this->token] . '&sort=dr.expire_on' . $url, true);

		$url = $this->filters(array('page'));

		$pagination = new Pagination();
		$pagination->total = $requestaccessdata_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link($this->extension_path . 'mpgdpr/requestaccessdata', $this->token.'=' . $this->session->data[$this->token] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($requestaccessdata_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($requestaccessdata_total - $this->config->get('config_limit_admin'))) ? $requestaccessdata_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $requestaccessdata_total, ceil($requestaccessdata_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['filter_status'] = $filter_status;
		$data['filter_request_id'] = $filter_request_id;
		$data['filter_date_send'] = $filter_date_send;
		$data['filter_email'] = $filter_email;
		$data['filter_date_start'] = $filter_date_start;
		$data['filter_date_end'] = $filter_date_end;
		$data['filter_time_lap_value'] = $filter_time_lap_value;
		$data['filter_time_lap'] = $filter_time_lap;

		$data['requestaccess_statuses'] = [];
		// 01-05-2022: updation start
		$data['requestaccess_statuses'][] = [
			'value' => \Mpgdpr\Mpgdpr :: REQUESTACCESS_EXPIRE,
			'text' => $this->language->get('text_expire'),
		];
		$data['requestaccess_statuses'][] = [
			'value' => \Mpgdpr\Mpgdpr :: REQUESTACCESS_CONFIRMED,
			'text' => $this->language->get('text_confirmed'),
		];
		$data['requestaccess_statuses'][] = [
			'value' => \Mpgdpr\Mpgdpr :: REQUESTACCESS_AWATING,
			'text' => $this->language->get('text_awating'),
		];
		$data['requestaccess_statuses'][] = [
			'value' => \Mpgdpr\Mpgdpr :: REQUESTACCESS_REPORTSEND,
			'text' => $this->language->get('text_reportsend'),
		];
		$data['requestaccess_statuses'][] = [
			'value' => \Mpgdpr\Mpgdpr :: REQUESTACCESS_DENY,
			'text' => $this->language->get('text_deny'),
		];
		// 01-05-2022: updation end
		$data['last_upload_code'] = '';
		$data['upload_file'] = '';
		if (isset($this->session->data['last_upload_code'])) {
			$data['last_upload_code'] = $this->session->data['last_upload_code'];
			$upload_info = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getUploadByCode($this->session->data['last_upload_code']);
			if ($upload_info) {
				$data['upload_file'] = $upload_info['name'];
			}
		}
		

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->viewLoad($this->extension_path . 'mpgdpr/requestaccessdata', $data));
	}

	protected function filters($excludes = array()) {
		$url = '';

		if (isset($this->request->get['filter_status']) && !in_array('filter_status', $excludes)) {
			$url .= '&filter_status=' . urlencode(html_entity_decode($this->request->get['filter_status'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_request_id']) && !in_array('filter_request_id', $excludes)) {
			$url .= '&filter_request_id=' . urlencode(html_entity_decode($this->request->get['filter_request_id'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_email']) && !in_array('filter_email', $excludes)) {
			$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_date_send']) && !in_array('filter_date_send', $excludes)) {
			$url .= '&filter_date_send=' . $this->request->get['filter_date_send'];
		}


		if (isset($this->request->get['filter_date_start']) && !in_array('filter_date_start', $excludes)) {
			$url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
		}

		if (isset($this->request->get['filter_date_end']) && !in_array('filter_date_end', $excludes)) {
			$url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
		}

		if (isset($this->request->get['filter_time_lap_value']) && !in_array('filter_time_lap_value', $excludes)) {
			$url .= '&filter_time_lap_value=' . urlencode(html_entity_decode($this->request->get['filter_time_lap_value'], ENT_QUOTES, 'UTF-8'));
		}
		if (isset($this->request->get['filter_time_lap']) && !in_array('filter_time_lap', $excludes)) {
			$url .= '&filter_time_lap=' . urlencode(html_entity_decode($this->request->get['filter_time_lap'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['sort']) && !in_array('sort', $excludes)) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order']) && !in_array('order', $excludes)) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page']) && !in_array('page', $excludes)) {
			$url .= '&page=' . $this->request->get['page'];
		}

		return $url;
	}

	// 01-05-2022: updation start
	public function approve() {
		$json = [];

		$this->load->language($this->extension_path . 'mpgdpr/requestaccessdata');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

		if (!$this->validateApprove()) {
			$json['error'] = $this->error;
		}

		if (!$json && !empty($this->request->get['mpgdpr_datarequest_id'])) {

			$request_info = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getRequestAccessData($this->request->get['mpgdpr_datarequest_id']);

			if (empty($request_info)) {
				$json['error'] = $this->language->get('error_invalid');
			}

			if (!$json) {
				// code found, lets check if expired or not. when status is awating confirmation
				$today = date('Y-m-d H:i:s');

				if (strtotime($today) > strtotime($request_info['expire_on']) && $request_info['status'] == \Mpgdpr\Mpgdpr :: REQUESTACCESS_AWATING ) {

					// expire the request as timeout
					$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->updateRequestAccessDataStatus($request_info['mpgdpr_datarequest_id'], \Mpgdpr\Mpgdpr :: REQUESTACCESS_EXPIRE);

					$json['error']['warning'] = $this->language->get('error_expire');
				}

			}

			if (!$json) {
				// code found, lets check if status awating or something else
				if ($request_info['status'] == \Mpgdpr\Mpgdpr :: REQUESTACCESS_AWATING ) {

					// complete the verification here
					$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->updateRequestAccessDataStatus($request_info['mpgdpr_datarequest_id'], \Mpgdpr\Mpgdpr :: REQUESTACCESS_CONFIRMED);

					$json['success'] = $this->language->get('success_approve');
				}

				// let check if what is the status of request now and response accordingly

				if ($request_info['status']==\Mpgdpr\Mpgdpr :: REQUESTACCESS_EXPIRE) {
					$json['error']['warning'] = $this->language->get('text_request_expired');
				} elseif ($request_info['status']==\Mpgdpr\Mpgdpr :: REQUESTACCESS_CONFIRMED) {
					$json['error']['warning'] = $this->language->get('text_request_confirmed');
				} elseif ($request_info['status']==\Mpgdpr\Mpgdpr :: REQUESTACCESS_DENY) {
					$json['error']['warning'] = $this->language->get('text_deny');
				}

				// here we check if json has respone or not. if not then say request unknow
				if (!$json) {
					$json['error']['warning'] = $this->language->get('text_request_unknown');
				}
			}

		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function validateApprove() {
		if (!$this->user->hasPermission('modify', $this->extension_path . 'mpgdpr/requestaccessdata')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
	// 01-05-2022: updation end

	// upload attachment file and return the name.
	public function uploadAttachment() {
		$this->load->language($this->extension_path . 'mpgdpr/requestaccessdata');
		$json = [];

		// create gdpr_accessdata reports folder first
		$dir = 'mpgdpr_accessdata/';
		$this->mpgdpr->mkdir(DIR_UPLOAD . $dir);

		if (!empty($this->request->files['file']['name']) && is_file($this->request->files['file']['tmp_name'])) {
			// Sanitize the filename
			$filename = basename(preg_replace('/[^a-zA-Z0-9\.\-\s+]/', '', html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8')));

			// Validate the filename length
			if ((utf8_strlen($filename) < 3) || (utf8_strlen($filename) > 64)) {
				$json['error'] = $this->language->get('error_filename');
			}

			// Allowed file extension types
			$allowed = [];

			$extension_allowed = preg_replace('~\r?\n~', "\n", $this->config->get('mpgdpr_file_ext_allowed'));

			$filetypes = explode("\n", $extension_allowed);

			foreach ($filetypes as $filetype) {
				$allowed[] = trim($filetype);
			}

			if (!in_array(strtolower(substr(strrchr($filename, '.'), 1)), $allowed)) {
				$json['error'] = $this->language->get('error_filetype');
			}

			// Allowed file mime types
			$allowed = [];

			$mime_allowed = preg_replace('~\r?\n~', "\n", $this->config->get('mpgdpr_file_mime_allowed'));

			$filetypes = explode("\n", $mime_allowed);

			foreach ($filetypes as $filetype) {
				$allowed[] = trim($filetype);
			}

			if (!in_array($this->request->files['file']['type'], $allowed)) {
				$json['error'] = $this->language->get('error_filetype');
			}

			// Check to see if any PHP files are trying to be uploaded
			$content = file_get_contents($this->request->files['file']['tmp_name']);

			if (preg_match('/\<\?php/i', $content)) {
				$json['error'] = $this->language->get('error_filetype');
			}

			// Return any upload error
			if ($this->request->files['file']['error'] != UPLOAD_ERR_OK) {
				$json['error'] = $this->language->get('error_upload_' . $this->request->files['file']['error']);
			}
		} else {
			$json['error'] = $this->language->get('error_upload');
		}


		if (!$json) {
			$file = $filename . '.' . token(32);

			move_uploaded_file($this->request->files['file']['tmp_name'], DIR_UPLOAD . $dir. $file);

			// Hide the uploaded file name so people can not link to it directly.
			$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

			$this->session->data['last_upload_code'] = $json['code'] = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addUpload($filename, $file);

			$json['filename'] = $filename;

			$json['success'] = $this->language->get('text_upload');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
 
	public function sendReportAction() {
		$json = [];
		$this->load->language($this->extension_path . 'mpgdpr/requestaccessdata');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
		if (empty($this->request->get['o']) || $this->request->get['o'] != 1) {
			$json['error'] = $this->language->get('error_invalid');
		}
		if (empty($this->request->post['attachment'])) {
			$json['attachment'] = $this->language->get('error_attachment');
		}
		
		if (empty($this->request->post['date_send']) || !empty($this->request->post['date_send']) && $this->request->post['date_send']=='0000-00-00' ) {
			$json['date_send'] = $this->language->get('error_date_send');
		}

		if (isset($this->session->data['last_upload_code']) && isset($this->request->post['attachment']) && ($this->session->data['last_upload_code'] != $this->request->post['attachment'])) {
			$json['attachment'] = $this->language->get('error_upload');
		}
		if (!$json) {
			// insert updates and close the popup 
			$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->updateRequestAccessDataAndSendReport($this->request->post);
			$json['text_reportsend'] = $this->language->get('text_reportsend');
			$json['success'] = $this->language->get('success_accessdata_sendreport');
			if (isset($this->session->data['last_upload_code'])) {
				unset($this->session->data['last_upload_code']);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	public function denyAction() {
		$json = [];
		$this->load->language($this->extension_path . 'mpgdpr/requestaccessdata');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
		if (empty($this->request->get['o']) || $this->request->get['o'] != 1) {
			$json['error'] = $this->language->get('error_invalid');
		}
		if ((utf8_strlen($this->request->post['denyreason']) < 3) || (utf8_strlen($this->request->post['denyreason']) > 10000)) {
				$json['denyreason'] = $this->language->get('error_denyreason');
		}

		if (!$json) {
			// insert updates and close the popup 
			$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->updateRequestAccessDataAndDeny($this->request->post);
			$json['text_deny'] = $this->language->get('text_deny');
			$json['success'] = $this->language->get('success_accessdata_deny');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', $this->extension_path . 'mpgdpr/requestaccessdata')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
