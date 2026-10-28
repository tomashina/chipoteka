<?php
class ControllerExtensionModuleGoogleLogin extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/google_login');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$settings = array(
				'module_google_login_status'        => !empty($this->request->post['module_google_login_status']) ? 1 : 0,
				'module_google_login_client_id'     => trim($this->request->post['module_google_login_client_id']),
				'module_google_login_client_secret' => trim($this->request->post['module_google_login_client_secret'])
			);

			$this->model_setting_setting->editSetting('module_google_login', $settings);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/module/google_login', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['error_client_id'] = isset($this->error['client_id']) ? $this->error['client_id'] : '';
		$data['error_client_secret'] = isset($this->error['client_secret']) ? $this->error['client_secret'] : '';

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/google_login', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/google_login', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$fields = array(
			'module_google_login_status',
			'module_google_login_client_id',
			'module_google_login_client_secret'
		);

		foreach ($fields as $field) {
			if (isset($this->request->post[$field])) {
				$data[$field] = $this->request->post[$field];
			} else {
				$data[$field] = $this->config->get($field);
			}
		}

		$data['callback_url'] = rtrim(HTTPS_CATALOG, '/') . '/index.php?route=extension/module/google_login/callback';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/google_login', $data));
	}

	public function install() {
		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_google_login', array(
			'module_google_login_status'        => 0,
			'module_google_login_client_id'     => '',
			'module_google_login_client_secret' => ''
		));
	}

	public function uninstall() {
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('module_google_login');
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/google_login')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!empty($this->request->post['module_google_login_status'])) {
			$client_id = isset($this->request->post['module_google_login_client_id']) ? trim($this->request->post['module_google_login_client_id']) : '';
			$client_secret = isset($this->request->post['module_google_login_client_secret']) ? trim($this->request->post['module_google_login_client_secret']) : '';

			if ($client_id === '' || substr($client_id, -27) !== '.apps.googleusercontent.com') {
				$this->error['client_id'] = $this->language->get('error_client_id');
			}

			if ($client_secret === '') {
				$this->error['client_secret'] = $this->language->get('error_client_secret');
			}
		}

		return !$this->error;
	}
}
