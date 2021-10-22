<?php
class ControllerExtensionAnalyticsGoogleAnalyticsExpert extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/analytics/google_analytics_expert');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		
		$this->load->model('extension/google_analytics_expert');
		
		$this->session->data['store_name'] = $this->model_extension_google_analytics_expert->getSelectedStoreName($this->request->get['store_id']);
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('analytics_google_analytics_expert', $this->request->post, $this->request->get['store_id']);

			$this->session->data['success'] = $this->language->get('text_success') . $this->session->data['store_name'];

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=analytics', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit') . $this->session->data['store_name'];
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_classic'] = $this->language->get('text_classic');
		$data['text_universal'] = $this->language->get('text_universal');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_exclude'] = $this->language->get('text_exclude');
		$data['text_include'] = $this->language->get('text_include');
		$data['text_no_track'] = $this->language->get('text_no_track');
		$data['text_track'] = $this->language->get('text_track');
		
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_gae_exclude_admin'] = $this->language->get('entry_gae_exclude_admin');
		$data['entry_gae_conversion_id'] = $this->language->get('entry_gae_conversion_id');
		$data['entry_gae_remarketing'] = $this->language->get('entry_gae_remarketing');
		$data['entry_gae_cookie'] = $this->language->get('entry_gae_cookie');
		$data['entry_gae_adwords'] = $this->language->get('entry_gae_adwords');
		$data['entry_gae_label'] = $this->language->get('entry_gae_label');
		$data['entry_gae_tracking'] = $this->language->get('entry_gae_tracking');
		
		$data['tab_analytics'] = $this->language->get('tab_analytics');
		$data['tab_adwords'] = $this->language->get('tab_adwords');
		$data['tab_installation'] = $this->language->get('tab_installation');
		$data['tab_faq'] = $this->language->get('tab_faq');
		$data['tab_support'] = $this->language->get('tab_support');
		$data['tab_events'] = $this->language->get('tab_events');
		$data['tab_log'] = $this->language->get('tab_log');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$data['help_gae_exclude_admin'] = $this->language->get('help_gae_exclude_admin');
		$data['help_gae_tracking_type'] = $this->language->get('help_gae_tracking_type');
		$data['help_gae_conversion_id'] = $this->language->get('help_gae_conversion_id');
		$data['help_gae_remarketing'] = $this->language->get('help_gae_remarketing');
		$data['help_gae_cookie'] = $this->language->get('help_gae_cookie');
		$data['help_gae_adwords'] = $this->language->get('help_gae_adwords');
		$data['help_gae_label'] = $this->language->get('help_gae_label');
		$data['help_gae_tracking'] = $this->language->get('help_gae_tracking');
		
		
		if (isset($this->session->data['success'])) {
			$data['success'] = sprintf($this->session->data['success'], $this->session->data['store_name']);

			unset($this->session->data['success']);
			unset($this->session->data['store_name']);
		} else {
			$data['success'] = '';
		}


		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['code'])) {
			$data['error_code'] = $this->error['code'];
		} else {
			$data['error_code'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=analytics', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/analytics/google_analytics_expert', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->request->get['store_id'], true)
		);

		$data['action'] = $this->url->link('extension/analytics/google_analytics_expert', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->request->get['store_id'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=analytics', true);
				
		if (isset($this->request->post['analytics_google_analytics_expert_status'])) {
			$data['analytics_google_analytics_expert_status'] = $this->request->post['analytics_google_analytics_expert_status'];
		} else {
			$data['analytics_google_analytics_expert_status'] = $this->model_setting_setting->getSettingValue('analytics_google_analytics_expert_status', $this->request->get['store_id']);
		}
		
		if (isset($this->request->post['analytics_google_analytics_expert_exclude_admin'])) {
			$data['analytics_google_analytics_expert_exclude_admin'] = $this->request->post['analytics_google_analytics_expert_exclude_admin']; 
		} else {
			$data['analytics_google_analytics_expert_exclude_admin'] = $this->model_setting_setting->getSettingValue('analytics_google_analytics_expert_exclude_admin', $this->request->get['store_id']);
		}
				
		if (isset($this->request->post['analytics_google_analytics_expert_property_id'])) {
			$data['analytics_google_analytics_expert_property_id'] = $this->request->post['analytics_google_analytics_expert_property_id']; 
		} else {
			$data['analytics_google_analytics_expert_property_id'] = $this->model_setting_setting->getSettingValue('analytics_google_analytics_expert_property_id', $this->request->get['store_id']);
		}
		
		if (isset($this->request->post['analytics_google_analytics_expert_remarketing'])) {
			$data['analytics_google_analytics_expert_remarketing'] = $this->request->post['analytics_google_analytics_expert_remarketing']; 
		} else {
			$data['analytics_google_analytics_expert_remarketing'] = $this->model_setting_setting->getSettingValue('analytics_google_analytics_expert_remarketing', $this->request->get['store_id']);
		}
		
		if (isset($this->request->post['analytics_google_analytics_expert_cookie'])) {
			$data['analytics_google_analytics_expert_cookie'] = $this->request->post['analytics_google_analytics_expert_cookie']; 
		} else {
			$data['analytics_google_analytics_expert_cookie'] = $this->model_setting_setting->getSettingValue('analytics_google_analytics_expert_cookie', $this->request->get['store_id']);
		}
		
		if (isset($this->request->post['analytics_google_analytics_expert_adwords'])) {
			$data['analytics_google_analytics_expert_adwords'] = $this->request->post['analytics_google_analytics_expert_adwords'];
		} else {
			$data['analytics_google_analytics_expert_adwords'] = $this->model_setting_setting->getSettingValue('analytics_google_analytics_expert_adwords', $this->request->get['store_id']);
		}
		
		if (isset($this->request->post['analytics_google_analytics_expert_conversion_id'])) {
			$data['analytics_google_analytics_expert_conversion_id'] = $this->request->post['analytics_google_analytics_expert_conversion_id']; 
		} else {
			$data['analytics_google_analytics_expert_conversion_id'] = $this->model_setting_setting->getSettingValue('analytics_google_analytics_expert_conversion_id', $this->request->get['store_id']);
		}
		
		if (isset($this->request->post['analytics_google_analytics_expert_label'])) {
			$data['analytics_google_analytics_expert_label'] = $this->request->post['analytics_google_analytics_expert_label']; 
		} else {
			$data['analytics_google_analytics_expert_label'] = $this->model_setting_setting->getSettingValue('analytics_google_analytics_expert_label', $this->request->get['store_id']);
		}
		
		$data['contact'] = HTTP_SERVER . 'view/template/extension/analytics/google_analytics_expert_installation.html#!/help_contact';
		$data['theme'] = $this->config->get('config_theme');
		$data['gaev'] = '7.0.1';
		$data['version'] = sprintf($this->language->get('version'), VERSION);
		$data['docs'] = HTTP_SERVER . 'view/template/extension/analytics/google_analytics_expert_installation.html';
		$vcheck = array("3.0.0.0","3.0.1.1","3.0.1.2","3.0.2.0","3.0.2.1");
		
		if (in_array(VERSION, $vcheck)) {
			$data['compatibility'] = '<span style="color:green">Compatible</span>';
		} else {
			$data['compatibility'] = '<span style="color:red">NOT Compatible</span>';
		}


$gae_code = <<<GAE
<script type="text/javascript">
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
GAE;

		$data['analytics_google_analytics_expert_code'] = $gae_code;
				
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/analytics/google_analytics_expert', $data));
	}
	

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/analytics/google_analytics_expert')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (isset($this->request->post['analytics_google_analytics_expert_status']) && empty($this->request->post['analytics_google_analytics_expert_property_id'])) {
			$this->error['warning'] = $this->language->get('error_gae_property_id');
		}
		
		if (isset($this->request->post['analytics_google_analytics_expert_adwords']) && $this->request->post['analytics_google_analytics_expert_adwords'] == 1 && empty($this->request->post['analytics_google_analytics_expert_conversion_id'])) {
			$this->error['warning'] = $this->language->get('error_gae_conversion_id');
		}
		
		if (isset($this->request->post['analytics_google_analytics_expert_adwords']) && $this->request->post['analytics_google_analytics_expert_adwords'] == 1 && empty($this->request->post['analytics_google_analytics_expert_label'])) {
			$this->error['warning'] = $this->language->get('error_gae_label');
		}
						
		if ($this->request->post['analytics_google_analytics_expert_exclude_admin'] == ''|| $this->request->post['analytics_google_analytics_expert_remarketing'] == '' || $this->request->post['analytics_google_analytics_expert_cookie'] == '' || $this->request->post['analytics_google_analytics_expert_status'] == '') {
			$this->error['warning'] = $this->language->get('error_empty');
		}			

		return !$this->error;
	}
	
	public function install() {
    	$this->load->model('setting/event');
    	//$this->model_extension_event->addEvent('google_analytics_expert', 'catalog/model/checkout/order/addOrder/before', 'extension/analytics/google_analytics_expert/getOrderProduct');
    	//$this->model_extension_event->addEvent('google_analytics_expert', 'catalog/model/checkout/order/addOrder/before', 'extension/analytics/google_analytics_expert/getOrderProductOptions');
    	//$this->model_setting_event->addEvent('google_analytics_expert', 'catalog/model/checkout/order/addOrder/after', 'extension/analytics/google_analytics_expert/ecommerce');
    	//$this->model_extension_event->addEvent('google_analytics_expert', 'catalog/controller/checkout/success/before', 'extension/analytics/google_analytics_expert/ecommerce');
    	//$this->model_extension_event->addEvent('google_analytics_expert', 'catalog/view/theme/*/template/common/success/after', 'extension/analytics/google_analytics_expert/ecommerce');
    	//$this->model_extension_event->addEvent('google_analytics_expert', 'catalog/view/common/success/before', 'extension/analytics/google_analytics_expert/ecommerce');
    	$this->model_setting_event->addEvent('google_analytics_expert', 'catalog/controller/checkout/success/after', 'extension/analytics/google_analytics_expert/ecommerce');
    }

	public function uninstall() {
    	$this->load->model('setting/event');
    	$this->model_setting_event->deleteEventByCode('google_analytics_expert');
	}
}
