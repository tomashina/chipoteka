<?php
class ControllerExtensionMpGdprRequestList extends \Mpgdpr\Controller {
	use \Mpgdpr\trait_mpgdpr;
	private $error = [];

	public function __construct($registry) {
		parent :: __construct($registry);
		$this->igniteTraitMpGdpr($registry);
	}

	public function index() {
		$this->load->language($this->extension_path . 'mpgdpr/requestlist');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addStyle('view/stylesheet/mpgdpr/mpgdpr.css');

		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

		if (isset($this->request->get['filter_request_id'])) {
			$filter_request_id = $this->request->get['filter_request_id'];
		} else {
			$filter_request_id = '';
		}

		if (isset($this->request->get['filter_type'])) {
			$filter_type = $this->request->get['filter_type'];
		} else {
			$filter_type = null;
		}

		if (isset($this->request->get['filter_email'])) {
			$filter_email = $this->request->get['filter_email'];
		} else {
			$filter_email = null;
		}

		if (isset($this->request->get['filter_useragent'])) {
			$filter_useragent = $this->request->get['filter_useragent'];
		} else {
			$filter_useragent = null;
		}

		if (isset($this->request->get['filter_server_ip'])) {
			$filter_server_ip = $this->request->get['filter_server_ip'];
		} else {
			$filter_server_ip = null;
		}

		if (isset($this->request->get['filter_client_ip'])) {
			$filter_client_ip = $this->request->get['filter_client_ip'];
		} else {
			$filter_client_ip = null;
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
			'href' => $this->url->link($this->extension_path . 'mpgdpr/requestlist', $this->token.'=' . $this->session->data[$this->token] . $url, true)
		];

		if (VERSION < '3.0.0.0') {
			$this->getAllLanguageMpgdpr($data);
		}

		$data['token'] = $this->session->data[$this->token];
		$data['get_token'] = $this->token;
		$data['extension_path'] = $this->extension_path;

		$data['delete'] = $this->url->link($this->extension_path . 'mpgdpr/requestlist/delete', $this->token.'=' . $this->session->data[$this->token] . $url, true);

		$data['requests'] = [];

		$filter_data = [
			'sort'  => $sort,
			'order' => $order,
			'filter_request_id' => $filter_request_id,
			'filter_type' => $filter_type,
			'filter_email' => $filter_email,
			'filter_useragent' => $filter_useragent,
			'filter_server_ip' => $filter_server_ip,
			'filter_client_ip' => $filter_client_ip,
			'filter_date_start' => $filter_date_start,
			'filter_date_end' => $filter_date_end,
			'filter_time_lap_value' => $filter_time_lap_value,
			'filter_time_lap' => $filter_time_lap,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		];

		$requestlist_total = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getTotalRequests($filter_data);

		$results = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getRequests($filter_data);

		$customer_model = $this->customerModelObj();

		foreach ($results as $result) {

			$customer_info = $this->{$customer_model}->getCustomer($result['customer_id']);
			$email = $result['email'];
			if ($customer_info) {
				$email = $customer_info['email'];
			}

			$data['requests'][] = [
				'mpgdpr_requestlist_id' => $result['mpgdpr_requestlist_id'],
				'email'        => $email,
				'type'        => $this->mpgdpr->getRequestName($result['requessttype']),
				/*13 sep 2019 gdpr session starts*/
				'custom_string'        => $result['custom_string'],//html_entity_decode($result['custom_string'], ENT_QUOTES, 'UTF-8'),
				/*13 sep 2019 gdpr session ends*/
				'acceptlanguage'        => $result['accept_language'],
				'useragent'        => $result['user_agent'],
				'server_ip'        => $result['server_ip'],
				'client_ip'        => $result['client_ip'],
				/*13 sep 2019 gdpr session starts*/
				'date_added'        => date($this->language->get('datetime_format'), strtotime($result['date_added'])) ,
				/*13 sep 2019 gdpr session ends*/
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

		$data['sort_requestlist_id'] = $this->url->link($this->extension_path . 'mpgdpr/requestlist', $this->token.'=' . $this->session->data[$this->token] . '&sort=r.mpgdpr_requestlist_id' . $url, true);
		$data['sort_requessttype'] = $this->url->link($this->extension_path . 'mpgdpr/requestlist', $this->token.'=' . $this->session->data[$this->token] . '&sort=r.requessttype' . $url, true);
		$data['sort_email'] = $this->url->link($this->extension_path . 'mpgdpr/requestlist', $this->token.'=' . $this->session->data[$this->token] . '&sort=r.email' . $url, true);
		$data['sort_date_added'] = $this->url->link($this->extension_path . 'mpgdpr/requestlist', $this->token.'=' . $this->session->data[$this->token] . '&sort=r.date_added' . $url, true);


		$url = $this->filters(array('page'));

		$pagination = new Pagination();
		$pagination->total = $requestlist_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link($this->extension_path . 'mpgdpr/requestlist', $this->token.'=' . $this->session->data[$this->token] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($requestlist_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($requestlist_total - $this->config->get('config_limit_admin'))) ? $requestlist_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $requestlist_total, ceil($requestlist_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['filter_request_id'] = $filter_request_id;
		$data['filter_type'] = $filter_type;
		$data['filter_email'] = $filter_email;
		$data['filter_useragent'] = $filter_useragent;
		$data['filter_server_ip'] = $filter_server_ip;
		$data['filter_client_ip'] = $filter_client_ip;
		$data['filter_date_start'] = $filter_date_start;
		$data['filter_date_end'] = $filter_date_end;
		$data['filter_time_lap_value'] = $filter_time_lap_value;
		$data['filter_time_lap'] = $filter_time_lap;

		$data['request_types'] = $this->mpgdpr->getRequestTypes();


		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->viewLoad($this->extension_path . 'mpgdpr/requestlist', $data));
	}

	protected function filters($excludes = array()) {
		$url = '';

		if (isset($this->request->get['filter_request_id']) && !in_array('filter_request_id', $excludes)) {
			$url .= '&filter_request_id=' . $this->request->get['filter_request_id'];
		}

		if (isset($this->request->get['filter_type']) && !in_array('filter_type', $excludes)) {
			$url .= '&filter_type=' . urlencode(html_entity_decode($this->request->get['filter_type'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_email']) && !in_array('filter_email', $excludes)) {
			$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_useragent']) && !in_array('filter_useragent', $excludes)) {
			$url .= '&filter_useragent=' . urlencode(html_entity_decode($this->request->get['filter_useragent'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_server_ip']) && !in_array('filter_server_ip', $excludes)) {
			$url .= '&filter_server_ip=' . urlencode(html_entity_decode($this->request->get['filter_server_ip'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_client_ip']) && !in_array('filter_client_ip', $excludes)) {
			$url .= '&filter_client_ip=' . urlencode(html_entity_decode($this->request->get['filter_client_ip'], ENT_QUOTES, 'UTF-8'));
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

	public function delete() {
		$this->load->language($this->extension_path . 'mpgdpr/requestlist');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $mpgdpr_requestlist_id) {
				$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->deleteRequestList($mpgdpr_requestlist_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->filters();

			$this->response->redirect($this->url->link($this->extension_path . 'mpgdpr/requestlist', $this->token.'=' . $this->session->data[$this->token] . $url, true));
		}

		$this->index();
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', $this->extension_path . 'mpgdpr/requestlist')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
	// 01-05-2022: updation start
	public function deleteRequestList() {
		$json = [];
		$this->load->language($this->extension_path . 'mpgdpr/requestlist');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
		if (!$this->validateDelete()) {
			$json['error'] = $this->error;
		}

		if (!$json && !empty($this->request->post['mpgdpr_requestlist_id'])) {
			$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->deleteRequestList($this->request->post['mpgdpr_requestlist_id']);
			$json['success'] = $this->language->get('text_success');
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	// 01-05-2022: updation end

}
