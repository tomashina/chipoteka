<?php

class ControllerExtensionMpGdprAccountDeleteMe extends \Mpgdpr\Controller {
	use \Mpgdpr\trait_mpgdpr_catalog;

	public function __construct($registry) {
		parent :: __construct($registry);
		$this->igniteTraitMpgdprCatalog($registry);
	}

	private $error = [];
	public function index() {

		if ($this->config->get('mpgdpr_login_gdprforms') && !$this->customer->getId()) {
			$this->session->data['redirect'] = $this->url->link($this->extension_path . 'mpgdpr/account/deleteme', '', true);
			$this->response->redirect($this->url->link('account/login', '', true));
		}

		if (!$this->config->get('mpgdpr_status')) {
			return new Action('error/not_found');
		}
		// 01-05-2022: updation start
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

		if (isset($this->request->get['filter_date_deletion'])) {
			$filter_date_deletion = $this->request->get['filter_date_deletion'];
		} else {
			$filter_date_deletion = null;
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

		$limit = 20;
		// 01-05-2022: updation end

		$this->load->language($this->extension_path . 'mpgdpr/deleteme');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');

		if (!$this->config->get('mpgdpr_hasright_todelete')) {
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr', '', true));
		}


		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			$customer_id = $this->customer->getId();
			// if customer is not logged in. then fetch customer_id from email.
			if (!$customer_id) {
				$customer_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerIdFromEmail($this->request->post['email']);
			}

			// add delete record
			$request_data = [
				'customer_id' => $customer_id,
				'email' => $this->request->post['email'],
				'date' => date('Y-m-d H:i:s'),
			];
			$mpgdpr_deleteme_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addDeleteMeRequest($request_data);

			// Add to request log
			/*13 sep 2019 gdpr session starts*/
			$request_data = [
				'customer_id' => $customer_id,
				'email' => $this->request->post['email'],
				'date' => date('Y-m-d H:i:s'),
				'custom_string' => '',
			];
			/*13 sep 2019 gdpr session ends*/
			// 01-05-2022: updation start
			$mpgdpr_requestlist_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEREQUESTDELETEME, $request_data);
			// 01-05-2022: updation end
			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr', '', true));

		}

		$this->document->setTitle($this->language->get('heading_title'));

		// 01-05-2022: updation start
		$data['scripts'] = [];
		$data['styles'] = [];
		if (VERSION >= '3.0.0.0') {
			$data['scripts'][] = 'catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js';
			$data['scripts'][] = 'catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js';
		} else {
			$data['scripts'][] = 'catalog/view/javascript/jquery/datetimepicker/moment.js';
		}
		$data['scripts'][] = 'catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js';
		$data['styles'][] = 'catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css';

		$url = $this->filters();

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_gdpr'),
			'href' => $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr', '', true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_gdpr_deleteme'),
			'href' => $this->url->link($this->extension_path . 'mpgdpr/account/deleteme', '' .  $url, true)
		];

		// 01-05-2022: updation end

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];

			unset($this->session->data['error']);
		} elseif (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_deleteme'] = $this->language->get('text_deleteme');

		$data['text_step1'] = $this->language->get('text_step1');
		$data['text_step2'] = $this->language->get('text_step2');
		$data['text_step3'] = $this->language->get('text_step3');
		$data['text_step4'] = $this->language->get('text_step4');

		// 01-05-2022: updation start
		$data['text_gdpr_deleteme_list'] = $this->language->get('text_gdpr_deleteme_list');
		$data['text_date_added'] = $this->language->get('text_date_added');
		$data['text_date_modified'] = $this->language->get('text_date_modified');
		$data['text_expire_on'] = $this->language->get('text_expire_on');
		$data['text_all'] = $this->language->get('text_all');
		$data['text_no_results'] = $this->language->get('text_no_results');
		// 01-05-2022: updation end

		$data['entry_email'] = $this->language->get('entry_email');

		// 01-05-2022: updation start
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_request_id'] = $this->language->get('entry_request_id');
		$data['entry_date_deletion'] = $this->language->get('entry_date_deletion');

		$data['entry_date_start'] = $this->language->get('entry_date_start');
		$data['entry_date_end'] = $this->language->get('entry_date_end');
		$data['entry_time_lap_value'] = $this->language->get('entry_time_lap_value');
		$data['entry_time_lap'] = $this->language->get('entry_time_lap');
		$data['entry_days'] = $this->language->get('entry_days');
		$data['entry_weeks'] = $this->language->get('entry_weeks');
		$data['entry_months'] = $this->language->get('entry_months');
		$data['entry_years'] = $this->language->get('entry_years');

		$data['column_request_id'] = $this->language->get('column_request_id');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_email'] = $this->language->get('column_email');
		$data['column_date_deletion'] = $this->language->get('column_date_deletion');
		$data['column_date'] = $this->language->get('column_date');
		$data['column_action'] = $this->language->get('column_action');

		$data['button_resentcode'] = $this->language->get('button_resentcode');
		$data['button_approve'] = $this->language->get('button_approve');
		$data['button_filter'] = $this->language->get('button_filter');
		$data['button_reset'] = $this->language->get('button_reset');
		// 01-05-2022: updation end


		$data['button_continue'] = $this->language->get('button_continue');
		$data['button_back'] = $this->language->get('button_back');

		$data['back'] = $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr', '', true);
		$data['action'] = $this->url->link($this->extension_path . 'mpgdpr/account/deleteme', '', true);

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = $this->customer->getEmail();
		}

		// Captcha
		if ($this->config->get('mpgdpr_captcha') == 'oc_captcha' && $this->config->get('mpgdpr_captcha_gdprforms')) {

			if (VERSION == '2.0.0.0') {
				$data['captcha'] = $this->mpgdpr->captcha($this->config->get('mpgdpr_captcha'), $this->error);

			} elseif (VERSION == '2.0.2.0') {
				$data['captcha'] = $this->mpgdpr->captcha($this->config->get('mpgdpr_captcha'), $this->error);
			} else {
				$data['captcha'] = '';
			}

		} else {
			$prefix_captcha_module = '';
			if (VERSION >= '3.0.0.0') {
				$prefix_captcha_module = 'captcha_';
			}
			if ($this->config->get($prefix_captcha_module.$this->config->get('mpgdpr_captcha') . '_status') && $this->config->get('mpgdpr_captcha_gdprforms')) {


				$data['captcha'] = $this->mpgdpr->captcha($this->config->get('mpgdpr_captcha'), $this->error);
			}  else {
				$data['captcha'] = '';
			}

		}

		// 01-05-2022: updation start
		$data['customer_id'] = $this->customer->getId();

		$data['requests'] = [];

		$filter_data = [
			'sort'  => $sort,
			'order' => $order,
			'filter_status' => $filter_status,
			'filter_request_id' => $filter_request_id,
			'filter_date_deletion' => $filter_date_deletion,
			'filter_date_start' => $filter_date_start,
			'filter_date_end' => $filter_date_end,
			'filter_time_lap_value' => $filter_time_lap_value,
			'filter_time_lap' => $filter_time_lap,
			'start' => ($page - 1) * $limit,
			'limit' => $limit
		];

		$requestanonymouse_total = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getTotalDeleteMeRequests($this->customer->getId(), $filter_data);

		$results = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getDeleteMeRequests($this->customer->getId(), $filter_data);

		// 01-05-2022: updation start
		$today = strtotime(date('Y-m-d H:i:s'));
		// 01-05-2022: updation end

		$this->load->model('account/customer');

		foreach ($results as $result) {

			$email = $result['email'];
			if (empty($email)) {
				$email = $this->customer->getEmail();
			}
			if (empty($email)) {
				$customer_info = $this->model_account_customer->getCustomer($result['customer_id']);
				if ($customer_info) {
					$email = $customer_info['email'];
				}
			}

			$status_text = '';
			// 01-05-2022: updation start
			if ($result['status']==\Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_EXPIRE) {
				$status_text = $this->language->get('text_expire');
			} elseif ($result['status']==\Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_CONFIRMED) {
				$status_text = $this->language->get('text_confirmed');
			} elseif ($result['status']==\Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_AWATING) {
				$status_text = $this->language->get('text_awating');
			} elseif ($result['status']==\Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_COMPLETE) {
				$status_text = $this->language->get('text_complete');
			} elseif ($result['status']==\Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_DENY) {
				$status_text = $this->language->get('text_deny');
			}

			$approve = '';
			if ($result['status'] == \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_AWATING) {
				$approve = $this->url->link($this->extension_path . 'mpgdpr/verification_deleteme','', true);
			}

			$resentcode = '';
			if ($result['status'] == \Mpgdpr\Mpgdpr :: REQUESTACCESS_AWATING && ($today > strtotime($result['expire_on']))) {
				$resentcode = $this->url->link($this->extension_path . 'mpgdpr/verification_deleteme/resentCode', 'mpgdpr_deleteme_id='.$result['mpgdpr_deleteme_id'].'&o=1', true);
			}
			// 01-05-2022: updation end

			$data['requests'][] = [
				'mpgdpr_deleteme_id' => $result['mpgdpr_deleteme_id'],
				'email'        => $email,
				'date_deletion'        => $result['date_deletion'] != '0000-00-00' ? $result['date_deletion'] : '',
				'status_text'        => $status_text,
				'status'        => $result['status'],
				'date_added'        => $result['date_added'],
				// 01-05-2022: updation end
				'expire_on'        => $result['expire_on'],
				'date_modified'        => $result['date_modified'] != '0000-00-00 00:00:00' ? $result['date_modified'] : '',
				'expire_on'        => $result['expire_on'],
				'expire'        => ($today > strtotime($result['expire_on'])),
				'approve'        => $approve,
				'resentcode'        => $resentcode,
				// 01-05-2022: updation end
			];
		}

		$url = $this->filters(['sort', 'order']);

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		$data['sort_deleteme_id'] = $this->url->link($this->extension_path . 'mpgdpr/account/deleteme', '&sort=d.mpgdpr_deleteme_id' . $url, true);
		$data['sort_status'] = $this->url->link($this->extension_path . 'mpgdpr/account/deleteme', '&sort=d.status' . $url, true);
		$data['sort_date_deletion'] = $this->url->link($this->extension_path . 'mpgdpr/account/deleteme', '&sort=d.date_deletion' . $url, true);
		$data['sort_date_added'] = $this->url->link($this->extension_path . 'mpgdpr/account/deleteme', '&sort=d.date_added' . $url, true);
		$data['sort_expire_on'] = $this->url->link($this->extension_path . 'mpgdpr/account/deleteme', '&sort=d.expire_on' . $url, true);

		$url = $this->filters(['page']);

		$pagination = new Pagination();
		$pagination->total = $requestanonymouse_total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link($this->extension_path . 'mpgdpr/account/deleteme', $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($requestanonymouse_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($requestanonymouse_total - $limit)) ? $requestanonymouse_total : ((($page - 1) * $limit) + $limit), $requestanonymouse_total, ceil($requestanonymouse_total / $limit));

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['filter_status'] = $filter_status;
		$data['filter_request_id'] = $filter_request_id;
		$data['filter_date_deletion'] = $filter_date_deletion;
		$data['filter_date_start'] = $filter_date_start;
		$data['filter_date_end'] = $filter_date_end;
		$data['filter_time_lap_value'] = $filter_time_lap_value;
		$data['filter_time_lap'] = $filter_time_lap;

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = [];
		}

		$data['deletion_statuses'] = [];
		// 01-05-2022: updation start
		$data['deletion_statuses'][] = [
			'value' => \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_EXPIRE,
			'text' => $this->language->get('text_expire'),
		];
		$data['deletion_statuses'][] = [
			'value' => \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_CONFIRMED,
			'text' => $this->language->get('text_confirmed'),
		];
		$data['deletion_statuses'][] = [
			'value' => \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_AWATING,
			'text' => $this->language->get('text_awating'),
		];
		$data['deletion_statuses'][] = [
			'value' => \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_COMPLETE,
			'text' => $this->language->get('text_complete'),
		];
		$data['deletion_statuses'][] = [
			'value' => \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_DENY,
			'text' => $this->language->get('text_deny'),
		];

		$data['requestanonymouse_confirmed'] = \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_CONFIRMED;
		$data['requestanonymouse_awating'] = \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_AWATING;

		// 01-05-2022: updation end

		// 01-05-2022: updation end

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$data['extension_path'] = $this->extension_path;

		$this->response->setOutput($this->viewLoad($this->extension_path . 'mpgdpr/account/deleteme', $data));
	}

	protected function filters($excludes = []) {
		$url = '';

		if (isset($this->request->get['filter_status']) && !in_array('filter_status', $excludes)) {
			$url .= '&filter_status=' . urlencode(html_entity_decode($this->request->get['filter_status'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_request_id']) && !in_array('filter_request_id', $excludes)) {
			$url .= '&filter_request_id=' . urlencode(html_entity_decode($this->request->get['filter_request_id'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_date_deletion']) && !in_array('filter_date_deletion', $excludes)) {
			$url .= '&filter_date_deletion=' . $this->request->get['filter_date_deletion'];
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

	private function validate() {
		if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if (!$this->config->get('mpgdpr_hasright_todelete')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!isset($this->error['email'])) {
			// check email is present in our customer table
			$customer_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerIdFromEmail($this->request->post['email']);
			if (!$customer_id) {
				$this->error['warning'] = $this->language->get('error_notcustomer');
			}

			// if customer is logged in then check if email customer id and login customer id is same.
			if ($this->customer->getId() && $customer_id != $this->customer->getId()) {
				$this->error['warning'] = $this->language->get('error_customerid_mismatch');
			}

			if ($customer_id && !isset($this->error['warning'])) {
				// check for max delete me request to today
				$total_requests = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getTodayDeleteMeRequest($customer_id);
				if ($total_requests >= $this->config->get('mpgdpr_maxrequests')) {
					$this->error['warning'] = sprintf($this->language->get('error_maxrequests'), $this->config->get('mpgdpr_maxrequests'));
				}
			}
		}

		// Captcha
		if ($this->config->get('mpgdpr_captcha') == 'oc_captcha' && $this->config->get('mpgdpr_captcha_gdprforms')) {

			if (VERSION == '2.0.0.0') {
				$captcha = $this->mpgdpr->captchaValidate($this->config->get('mpgdpr_captcha'));

				if (!$captcha['success']) {
					$this->error['captcha'] = $this->language->get('error_captcha');
				}

			} elseif (VERSION == '2.0.2.0') {
				$captcha = $this->mpgdpr->captchaValidate($this->config->get('mpgdpr_captcha'));

				if ($captcha) {
					$this->error['captcha'] = $this->language->get('error_captcha');
				}
			}

		} else {
			$prefix_captcha_module = '';
			if (VERSION >= '3.0.0.0') {
				$prefix_captcha_module = 'captcha_';
			}
			if ($this->config->get($prefix_captcha_module.$this->config->get('mpgdpr_captcha') . '_status') && $this->config->get('mpgdpr_captcha_gdprforms')) {
				$captcha = $this->mpgdpr->captchaValidate($this->config->get('mpgdpr_captcha'));
				if ($captcha) {
					$this->error['captcha'] = $captcha;
				}
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}
}