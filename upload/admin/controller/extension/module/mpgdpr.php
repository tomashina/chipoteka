<?php

class ControllerExtensionModuleMpGdpr extends \Mpgdpr\Controller {
	use \Mpgdpr\trait_mpgdpr;
	private $error = [];

	private $installed = [];
	private $files = [
		'extension/mpgdpr/policyacceptance',
		'extension/mpgdpr/requestaccessdata',
		'extension/mpgdpr/requestanonymouse',
		'extension/mpgdpr/requestlist',
		'extension/module/mpgdpr'
	];
	// 29 dec 2022 changes starts
	private $events_code = 'module_mpgdpr';
	private $events = [
		[
			'trigger' => 'admin/view/common/column_left/before',
			'action' => 'extension/module/mpgdpr/getMenu'
		],[
			'trigger' => 'admin/view/customer/customer_list/after',
			'action' => 'extension/module/mpgdpr/customerListProcessRestrict'
		],[
			'trigger' => 'catalog/view/account/account/after',
			'action' => 'extension/mpgdpr/event/accountAccount'
		],[
			'trigger' => 'catalog/view/extension/module/account/after',
			'action' => 'extension/mpgdpr/event/moduleAccount'
		],[
			'trigger' => 'catalog/view/information/contact/before',
			'action' => 'extension/mpgdpr/event/informationContact'
		],[
			'trigger' => 'catalog/view/information/contact/after',
			'action' => 'extension/mpgdpr/event/informationContactAfter'
		],[
			'trigger' => 'catalog/model/account/customer/addCustomer/after',
			'action' => 'extension/mpgdpr/event/modelAddCustomer'
		],[
			'trigger' => 'catalog/controller/account/register/before',
			'action' => 'extension/mpgdpr/event/accountRegisterBefore'
		],[
			'trigger' => 'catalog/controller/account/register/after',
			'action' => 'extension/mpgdpr/event/accountRegisterAfter'
		],[
			'trigger' => 'catalog/controller/checkout/register/before',
			'action' => 'extension/mpgdpr/event/checkoutRegisterBefore'
		],[
			'trigger' => 'catalog/controller/checkout/register/after',
			'action' => 'extension/mpgdpr/event/checkoutRegisterAfter'
		],[
			'trigger' => 'catalog/controller/checkout/register/save/before',
			'action' => 'extension/mpgdpr/event/checkoutRegisterSaveBefore'
		],[
			'trigger' => 'catalog/controller/checkout/register/save/after',
			'action' => 'extension/mpgdpr/event/checkoutRegisterSaveAfter'
		],[
			'trigger' => 'catalog/controller/checkout/payment_method/before',
			'action' => 'extension/mpgdpr/event/checkoutPaymentMethodBefore'
		],[
			'trigger' => 'catalog/controller/checkout/payment_method/after',
			'action' => 'extension/mpgdpr/event/checkoutPaymentMethodAfter'
		],[
			'trigger' => 'catalog/controller/checkout/payment_method/save/before',
			'action' => 'extension/mpgdpr/event/checkoutPaymentMethodSaveBefore'
		],[
			'trigger' => 'catalog/controller/checkout/payment_method/save/after',
			'action' => 'extension/mpgdpr/event/checkoutPaymentMethodSaveAfter'
		],[
			'trigger' => 'catalog/model/checkout/order/addOrderHistory/after',
			'action' => 'extension/mpgdpr/event/modelAddOrderHistory'
		],[
			'trigger' => 'catalog/view/common/header/before',
			'action' => 'extension/mpgdpr/event/commonHeaderBefore',
			// 'sort_order' => 513
		],[
			'trigger' => 'catalog/view/common/header/after',
			'action' => 'extension/mpgdpr/event/commonHeader',
			// 'sort_order' => 513
		],[
			'trigger' => 'catalog/controller/extension/analytics/google/after',
			'action' => 'extension/mpgdpr/event/analyticsGoogle'
		],[
			'trigger' => 'catalog/view/product/product/after',
			'action' => 'extension/mpgdpr/event/productProduct'
		]
	];
	// 29 dec 2022 changes ends

	public function __construct($registry) {
		parent :: __construct($registry);
		$this->igniteTraitMpGdpr($registry);

		// 29 dec 2022 changes starts
		/* OC2.3 lower when extension folder not exist starts */
		if (VERSION <= '2.2.0.0') {
			foreach ($this->files as $key => $value) {
				// remove extension path from begning
				$this->files[$key] = substr($value['trigger'], strlen($this->extension_path . ''));
			}
		}
		/* OC2.3 lower when extension folder not exist ends */

		/* OC2.3x event: view/after fix starts */
		if (VERSION > '2.2.0.0' && VERSION <= '2.3.0.2') {

			foreach ($this->events as $key => $value) {

					if (strpos($value['trigger'], 'admin/') !== false) {
						continue;
					}

					$trigger_parts = explode('/', $value['trigger']);
					$tigger_end = end($trigger_parts);

					$str_part = 'catalog/view/';
					if (strpos($value['trigger'], 'catalog/view') !== false &&  $tigger_end === 'after') {
						$this->events[$key]['trigger'] = $str_part . '*/' . substr($value['trigger'], strlen($str_part));
					}

			}
		}
		/* OC2.3x event: view/after fix ends */
		// 29 dec 2022 changes ends
	}

	public function install() {
		// run table installer
		$this->mpgdpr->install();

		// Add permissions to extension files dynamically
		$this->addFilesInPermissions($this->files);

		// 29 dec 2022 changes starts
		$this->createEvents($this->events, $this->events_code);
		// 29 dec 2022 changes ends
	}

	public function uninstall() {
		// 29 dec 2022 changes starts
		$this->removeEventsByCode($this->events_code);
		// 29 dec 2022 changes ends
	}

	// 29 dec 2022 changes starts
	// ajax callable
	public function activateEvents() {
		$json = [];

		if (($this->request->server['REQUEST_METHOD'] == 'GET') && $this->accessValidate() && isset($this->request->get['ae']) && $this->request->get['ae'] == '1') {

			$this->load->language($this->extension_path . 'module/mpgdpr');
			$this->load->model($this->model_file['extension/event']['path']);

			$disable_events = $this->areEventsDisable($this->events_code);

			if ($disable_events) {
				foreach ($disable_events as $event_id) {
					$this->{$this->model_file['extension/event']['obj']}->enableEvent($event_id);
				}

				$json['success'] = $this->language->get('text_success_activate_events');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	// 29 dec 2022 changes ends

	private function addFilesInPermissions($files) {
		if ($this->user->hasPermission('modify', $this->extension_path . 'module/mpgdpr')) {
			$this->load->model('user/user_group');
			foreach ($files as $file) {
				// remove list of files from permissions array to avoid troubles
				$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', $file);
				$this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', $file);

				$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', $file);
				$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', $file);
			}
		}
	}

	private function detectFilesForPermissions() {
		$this->load->model('user/user_group');
		$user_group = $this->model_user_user_group->getUserGroup($this->user->getGroupId());

		$files = [];

		foreach ($this->files as $file) {
			if (!in_array($file, $user_group['permission']['access']) || !in_array($file, $user_group['permission']['modify'])) {
				$files[] = $file;
			}
		}

		return $files;
	}

	public function updatePermissions() {
		$json = [];
		$this->load->language($this->extension_path . 'module/mpgdpr');

		$this->addFilesInPermissions($this->detectFilesForPermissions());
		$json['success'] = $this->language->get('text_success_files_permission');
		$json['timeout'] = 1500;
		$json['redirect'] = str_replace("&amp;", "&", $this->url->link($this->extension_path . 'module/mpgdpr', $this->token.'=' . $this->session->data[$this->token], true));

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function moduleIsInstalled($module, $type='module') {
		if (empty($this->installed[$type])) {

			$this->load->model($this->model_file['extension/extension']['path']);
			$extensions = $this->{$this->model_file['extension/extension']['obj']}->getInstalled($type);

			foreach ($extensions as $extension) {
				$this->installed[$type][] = $extension;
			}

		}

		return in_array($module, $this->installed[$type]);
	}

	public function index() {

		$this->load->language($this->extension_path . 'module/mpgdpr');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addStyle('view/stylesheet/mpgdpr/mpgdpr.css');

		$this->document->addStyle('view/javascript/mpgdpr/colorpicker/css/bootstrap-colorpicker.css');
		$this->document->addScript('view/javascript/mpgdpr/colorpicker/js/bootstrap-colorpicker.js');

		// run alter table queries
		$this->mpgdpr->alterTables();

		// show a alert message for files that are not in premissions list
		if ($this->user->hasPermission('modify', $this->extension_path . 'module/mpgdpr')) {
			$data['files'] = $this->detectFilesForPermissions();
		} else {
			$data['files'] = [];
		}

		// 29 dec 2022 changes starts
		$data['text_disable_events'] = '';
		$data['disable_events'] = false;
		if ($this->user->hasPermission('modify', $this->extension_path . 'module/mpgdpr')) {
			$this->createEvents($this->events, $this->events_code);
			$disable_events = $this->areEventsDisable($this->events_code);
			if ($disable_events) {
				$data['disable_events'] = true;
				$data['text_disable_events'] = $this->language->get('text_disable_events');
			}
		}
		// 29 dec 2022 changes ends

		$this->load->model('setting/setting');
		if (isset($this->request->get['store_id'])) {
			$store_id = $data['store_id'] = $this->request->get['store_id'];
		} else {
			$store_id = $data['store_id'] = 0;
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			$this->model_setting_setting->editSetting('mpgdpr', $this->request->post, $store_id);

			if (VERSION >= '3.0.0.0') {
				$post = array();
				$post['module_mpgdpr_status'] = $this->request->post['mpgdpr_status'];
				$this->model_setting_setting->editSetting('module_mpgdpr', $post, $store_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link($this->extension_path . 'module/mpgdpr', $this->token.'=' . $this->session->data[$this->token] . '&store_id=' . $store_id, true));
		}

		if (VERSION < '3.0.0.0') {
			$this->getAllLanguageMpgdpr($data);
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

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->token.'=' . $this->session->data[$this->token], true)
		];

		$this->breadcrumbs($data);

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->extension_path . 'module/mpgdpr', $this->token.'=' . $this->session->data[$this->token], true)
		];

		$data['action'] = $this->url->link($this->extension_path . 'module/mpgdpr', $this->token.'=' . $this->session->data[$this->token] . '&store_id=' . $store_id, true);
		$data['token'] = $this->session->data[$this->token];
		$data['get_token'] = $this->token;
		$data['extension_path'] = $this->extension_path;
		// 01-05-2022: updation start
		$data['texteditor'] = $this->textEditor($data);
		// 01-05-2022: updation end

		$data['stores'] = [];
		$this->load->model('setting/store');
		$stores = $this->model_setting_store->getStores();
		$data['stores'][] = [
			'name' => $this->language->get('text_default'),
			'store_id' => '0',
			'href' => $this->url->link($this->extension_path . 'module/mpgdpr', $this->token.'=' . $this->session->data[$this->token] .'&store_id=0', true)

		];
		$data['store_name'] = $this->language->get('text_default');
		foreach ($stores as $store) {
			$data['stores'][] = [
				'name' => $store['name'],
				'store_id' => $store['store_id'],
				'href' => $this->url->link($this->extension_path . 'module/mpgdpr', $this->token.'=' . $this->session->data[$this->token] .'&store_id=' . $store['store_id'], true)
			];
			if ($store['store_id'] == $store_id) {
				$data['store_name'] = $store['name'];
			}
		}

		$this->load->model('catalog/information');

		$information_pages = $this->model_catalog_information->getInformations();

		$data['information_pages'] = [];

		foreach ($information_pages as $information_page) {
			$data['information_pages'][] = [
				'information_id' => $information_page['information_id'],
				'title' => $information_page['title'],
			];
		}

		$this->load->model('localisation/language');
		$data['languages'] = $this->getLanguages($this->model_localisation_language->getLanguages());

		$this->load->model($this->model_file['extension/extension']['path']);

		$data['captchas'] = [];

		if (VERSION >= '2.1.0.1') {
			// Get a list of installed captchas
			$extensions = $this->{$this->model_file['extension/extension']['obj']}->getInstalled('captcha');

			foreach ($extensions as $code) {
				$this->load->language($this->extension_path . 'captcha/' . $code);

				// if ($this->config->get($this->extension_prefix['captcha'] . $code . '_status')) {
					$data['captchas'][] = [
						'text'  => $this->language->get('heading_title') . ' - ' . ($this->config->get($this->extension_prefix['captcha'] . $code . '_status') ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
						'value' => $code
					];
				// }
			}
		} else {
			if (VERSION == '2.0.2.0') {
				if ($this->config->get('config_google_captcha_status')) {
					$data['captchas'][] = [
						'text'  => $this->language->get('text_oc_captcha'),
						'value' => 'oc_captcha'
					];
				}
			}
			if (VERSION < '2.0.2.0') {
				$data['captchas'][] = [
					'text'  => $this->language->get('text_oc_captcha'),
					'value' => 'oc_captcha'
				];
			}
		}

		$this->load->language($this->extension_path . 'module/mpgdpr');

		$module = $this->model_setting_setting->getSetting('mpgdpr', $store_id);


		if (isset($this->request->post['mpgdpr_status'])) {
			$data['mpgdpr_status'] = $this->request->post['mpgdpr_status'];
		} elseif (isset($module['mpgdpr_status'])) {
			$data['mpgdpr_status'] = $module['mpgdpr_status'];
		} else {
			$data['mpgdpr_status'] = 0;
		}
		// 01-05-2022: updation start
		if (isset($this->request->post['mpgdpr_default_google_analytic'])) {
			$data['mpgdpr_default_google_analytic'] = $this->request->post['mpgdpr_default_google_analytic'];
		} elseif (isset($module['mpgdpr_default_google_analytic'])) {
			$data['mpgdpr_default_google_analytic'] = $module['mpgdpr_default_google_analytic'];
		} else {
			$data['mpgdpr_default_google_analytic'] = 0;
		}
		if (isset($this->request->post['mpgdpr_policy_data'])) {
			$data['mpgdpr_policy_data'] = $this->request->post['mpgdpr_policy_data'];
		} elseif (isset($module['mpgdpr_policy_data'])) {
			$data['mpgdpr_policy_data'] = $module['mpgdpr_policy_data'];
		} else {
			$data['mpgdpr_policy_data'] = 0;
		}
		// 01-05-2022: updation end
		if (isset($this->request->post['mpgdpr_acceptpolicy_customer'])) {
			$data['mpgdpr_acceptpolicy_customer'] = $this->request->post['mpgdpr_acceptpolicy_customer'];
		} elseif (isset($module['mpgdpr_acceptpolicy_customer'])) {
			$data['mpgdpr_acceptpolicy_customer'] = $module['mpgdpr_acceptpolicy_customer'];
		} else {
			$data['mpgdpr_acceptpolicy_customer'] = 0;
		}

		if (isset($this->request->post['mpgdpr_policy_customer'])) {
			$data['mpgdpr_policy_customer'] = $this->request->post['mpgdpr_policy_customer'];
		} elseif (isset($module['mpgdpr_policy_customer'])) {
			$data['mpgdpr_policy_customer'] = $module['mpgdpr_policy_customer'];
		} else {
			$data['mpgdpr_policy_customer'] = 0;
		}

		if (isset($this->request->post['mpgdpr_acceptpolicy_contactus'])) {
			$data['mpgdpr_acceptpolicy_contactus'] = $this->request->post['mpgdpr_acceptpolicy_contactus'];
		} elseif (isset($module['mpgdpr_acceptpolicy_contactus'])) {
			$data['mpgdpr_acceptpolicy_contactus'] = $module['mpgdpr_acceptpolicy_contactus'];
		} else {
			$data['mpgdpr_acceptpolicy_contactus'] = 0;
		}

		if (isset($this->request->post['mpgdpr_policy_contactus'])) {
			$data['mpgdpr_policy_contactus'] = $this->request->post['mpgdpr_policy_contactus'];
		} elseif (isset($module['mpgdpr_policy_contactus'])) {
			$data['mpgdpr_policy_contactus'] = $module['mpgdpr_policy_contactus'];
		} else {
			$data['mpgdpr_policy_contactus'] = 0;
		}

		if (isset($this->request->post['mpgdpr_acceptpolicy_checkout'])) {
			$data['mpgdpr_acceptpolicy_checkout'] = $this->request->post['mpgdpr_acceptpolicy_checkout'];
		} elseif (isset($module['mpgdpr_acceptpolicy_checkout'])) {
			$data['mpgdpr_acceptpolicy_checkout'] = $module['mpgdpr_acceptpolicy_checkout'];
		} else {
			$data['mpgdpr_acceptpolicy_checkout'] = 0;
		}

		if (isset($this->request->post['mpgdpr_policy_checkout'])) {
			$data['mpgdpr_policy_checkout'] = $this->request->post['mpgdpr_policy_checkout'];
		} elseif (isset($module['mpgdpr_policy_checkout'])) {
			$data['mpgdpr_policy_checkout'] = $module['mpgdpr_policy_checkout'];
		} else {
			$data['mpgdpr_policy_checkout'] = 0;
		}

		if (isset($this->request->post['mpgdpr_export_format'])) {
			$data['mpgdpr_export_format'] = $this->request->post['mpgdpr_export_format'];
		} elseif (isset($module['mpgdpr_export_format'])) {
			$data['mpgdpr_export_format'] = $module['mpgdpr_export_format'];
		} else {
			$data['mpgdpr_export_format'] = 'csv';
		}

		if (isset($this->request->post['mpgdpr_hasright_todelete'])) {
			$data['mpgdpr_hasright_todelete'] = $this->request->post['mpgdpr_hasright_todelete'];
		} elseif (isset($module['mpgdpr_hasright_todelete'])) {
			$data['mpgdpr_hasright_todelete'] = $module['mpgdpr_hasright_todelete'];
		} else {
			$data['mpgdpr_hasright_todelete'] = 0;
		}

		if (isset($this->request->post['mpgdpr_maxrequests'])) {
			$data['mpgdpr_maxrequests'] = $this->request->post['mpgdpr_maxrequests'];
		} elseif (isset($module['mpgdpr_maxrequests'])) {
			$data['mpgdpr_maxrequests'] = $module['mpgdpr_maxrequests'];
		} else {
			$data['mpgdpr_maxrequests'] = 3;
		}
		/*// for 3x versions
		if (isset($this->request->post['mpgdpr_keyword'])) {
			$data['mpgdpr_keyword'] = $this->request->post['mpgdpr_keyword'];
		} elseif (isset($module['mpgdpr_keyword'])) {
			$data['mpgdpr_keyword'] = $module['mpgdpr_keyword'];
		} else {
			$data['mpgdpr_keyword'] = '';
		}
		// for 2x or less version
		if (isset($this->request->post['mpgdpr_keyword'])) {
			$data['mpgdpr_keyword'] = $this->request->post['mpgdpr_keyword'];
		} else {
			$data['mpgdpr_keyword'] = $this->config->get('mpgdpr_keyword');
		}*/

		if (isset($this->request->post['mpgdpr_login_gdprforms'])) {
			$data['mpgdpr_login_gdprforms'] = $this->request->post['mpgdpr_login_gdprforms'];
		} elseif (isset($module['mpgdpr_login_gdprforms'])) {
			$data['mpgdpr_login_gdprforms'] = $module['mpgdpr_login_gdprforms'];
		} else {
			$data['mpgdpr_login_gdprforms'] = 0;
		}

		if (isset($this->request->post['mpgdpr_captcha_gdprforms'])) {
			$data['mpgdpr_captcha_gdprforms'] = $this->request->post['mpgdpr_captcha_gdprforms'];
		} elseif (isset($module['mpgdpr_captcha_gdprforms'])) {
			$data['mpgdpr_captcha_gdprforms'] = $module['mpgdpr_captcha_gdprforms'];
		} else {
			$data['mpgdpr_captcha_gdprforms'] = 0;
		}

		if (isset($this->request->post['mpgdpr_captcha'])) {
			$data['mpgdpr_captcha'] = $this->request->post['mpgdpr_captcha'];
		} elseif (isset($module['mpgdpr_captcha'])) {
			$data['mpgdpr_captcha'] = $module['mpgdpr_captcha'];
		} else {
			$data['mpgdpr_captcha'] = 0;
		}

		if (isset($this->request->post['mpgdpr_services'])) {
			$data['mpgdpr_services'] = $this->request->post['mpgdpr_services'];
		} elseif (isset($module['mpgdpr_services'])) {
			$data['mpgdpr_services'] = (array)$module['mpgdpr_services'];
		} else {
			$data['mpgdpr_services'] = [];
		}

		if (isset($this->request->post['mpgdpr_timeout'])) {
			$data['mpgdpr_timeout'] = $this->request->post['mpgdpr_timeout'];
		} elseif (isset($module['mpgdpr_timeout'])) {
			$data['mpgdpr_timeout'] = (array)$module['mpgdpr_timeout'];
		} else {
			$data['mpgdpr_timeout'] = [];
		}


		if (isset($this->request->post['mpgdpr_file_ext_allowed'])) {
			$data['mpgdpr_file_ext_allowed'] = $this->request->post['mpgdpr_file_ext_allowed'];
		} elseif (isset($module['mpgdpr_file_ext_allowed'])) {
			$data['mpgdpr_file_ext_allowed'] = $module['mpgdpr_file_ext_allowed'];
		} else {
			$data['mpgdpr_file_ext_allowed'] = $this->config->get('config_file_ext_allowed');
		}

		if (isset($this->request->post['mpgdpr_file_mime_allowed'])) {
			$data['mpgdpr_file_mime_allowed'] = $this->request->post['mpgdpr_file_mime_allowed'];
		} elseif (isset($module['mpgdpr_file_mime_allowed'])) {
			$data['mpgdpr_file_mime_allowed'] = $module['mpgdpr_file_mime_allowed'];
		} else {
			$data['mpgdpr_file_mime_allowed'] = $this->config->get('config_file_mime_allowed');
		}

		// cb = cookie bar aka cookie consent bar
		if (isset($this->request->post['mpgdpr_cbstatus'])) {
			$data['mpgdpr_cbstatus'] = $this->request->post['mpgdpr_cbstatus'];
		} elseif (isset($module['mpgdpr_cbstatus'])) {
			$data['mpgdpr_cbstatus'] = $module['mpgdpr_cbstatus'];
		} else {
			$data['mpgdpr_cbstatus'] = 0;
		}

		if (isset($this->request->post['mpgdpr_cbpolicy'])) {
			$data['mpgdpr_cbpolicy'] = $this->request->post['mpgdpr_cbpolicy'];
		} elseif (isset($module['mpgdpr_cbpolicy'])) {
			$data['mpgdpr_cbpolicy'] = $module['mpgdpr_cbpolicy'];
		} else {
			$data['mpgdpr_cbpolicy'] = 0;
		}

		if (isset($this->request->post['mpgdpr_cbpolicy_page'])) {
			$data['mpgdpr_cbpolicy_page'] = $this->request->post['mpgdpr_cbpolicy_page'];
		} elseif (isset($module['mpgdpr_cbpolicy_page'])) {
			$data['mpgdpr_cbpolicy_page'] = $module['mpgdpr_cbpolicy_page'];
		} else {
			$data['mpgdpr_cbpolicy_page'] = 0;
		}


		if (isset($this->request->post['mpgdpr_cbinitial'])) {
			$data['mpgdpr_cbinitial'] = $this->request->post['mpgdpr_cbinitial'];
		} elseif (isset($module['mpgdpr_cbinitial'])) {
			$data['mpgdpr_cbinitial'] = $module['mpgdpr_cbinitial'];
		} else {
			$data['mpgdpr_cbinitial'] = 0;
		}

		if (isset($this->request->post['mpgdpr_cbaction_close'])) {
			$data['mpgdpr_cbaction_close'] = $this->request->post['mpgdpr_cbaction_close'];
		} elseif (isset($module['mpgdpr_cbaction_close'])) {
			$data['mpgdpr_cbaction_close'] = $module['mpgdpr_cbaction_close'];
		} else {
			$data['mpgdpr_cbaction_close'] = 0;
		}

		if (isset($this->request->post['mpgdpr_cbshowagain'])) {
			$data['mpgdpr_cbshowagain'] = $this->request->post['mpgdpr_cbshowagain'];
		} elseif (isset($module['mpgdpr_cbshowagain'])) {
			$data['mpgdpr_cbshowagain'] = $module['mpgdpr_cbshowagain'];
		} else {
			$data['mpgdpr_cbshowagain'] = 0;
		}

		if (isset($this->request->post['mpgdpr_cbpptrack'])) {
			$data['mpgdpr_cbpptrack'] = $this->request->post['mpgdpr_cbpptrack'];
		} elseif (isset($module['mpgdpr_cbpptrack'])) {
			$data['mpgdpr_cbpptrack'] = $module['mpgdpr_cbpptrack'];
		} else {
			$data['mpgdpr_cbpptrack'] = 0;
		}

		if (isset($this->request->post['mpgdpr_cookie_stricklyrequired'])) {
			$data['mpgdpr_cookie_stricklyrequired'] = $this->request->post['mpgdpr_cookie_stricklyrequired'];
		} elseif (isset($module['mpgdpr_cookie_stricklyrequired'])) {
			$data['mpgdpr_cookie_stricklyrequired'] = $module['mpgdpr_cookie_stricklyrequired'];
		} else {
			$data['mpgdpr_cookie_stricklyrequired'] = implode("\n", array_map("trim", explode(",", "PHPSESSID,default,language,currency,cookieconsent_status,mpcookie_preferencesdisable")));
		}

		if (isset($this->request->post['mpgdpr_cookie_analytics'])) {
			$data['mpgdpr_cookie_analytics'] = $this->request->post['mpgdpr_cookie_analytics'];
		} elseif (isset($module['mpgdpr_cookie_analytics'])) {
			$data['mpgdpr_cookie_analytics'] = $module['mpgdpr_cookie_analytics'];
		} else {
			$data['mpgdpr_cookie_analytics'] = implode("\n", array_map("trim", explode(",", "_ga,_gid,_gat,__atuvc,__atuvs,__utma,__cfduid")));
		}
		// 01-05-2022: updation start
		if (isset($this->request->post['mpgdpr_cookie_analytics_allow'])) {
			$data['mpgdpr_cookie_analytics_allow'] = $this->request->post['mpgdpr_cookie_analytics_allow'];
		} elseif (isset($module['mpgdpr_cookie_analytics_allow'])) {
			$data['mpgdpr_cookie_analytics_allow'] = $module['mpgdpr_cookie_analytics_allow'];
		} else {
			$data['mpgdpr_cookie_analytics_allow'] = "";
		}
		if (isset($this->request->post['mpgdpr_cookie_analytics_deny'])) {
			$data['mpgdpr_cookie_analytics_deny'] = $this->request->post['mpgdpr_cookie_analytics_deny'];
		} elseif (isset($module['mpgdpr_cookie_analytics_deny'])) {
			$data['mpgdpr_cookie_analytics_deny'] = $module['mpgdpr_cookie_analytics_deny'];
		} else {
			$data['mpgdpr_cookie_analytics_deny'] = "";
		}
		// 01-05-2022: updation end
		if (isset($this->request->post['mpgdpr_cookie_marketing'])) {
			$data['mpgdpr_cookie_marketing'] = $this->request->post['mpgdpr_cookie_marketing'];
		} elseif (isset($module['mpgdpr_cookie_marketing'])) {
			$data['mpgdpr_cookie_marketing'] = $module['mpgdpr_cookie_marketing'];
		} else {
			$data['mpgdpr_cookie_marketing'] = implode("\n", array_map("trim", explode(",", "_gads,IDE")));
		}
		// 01-05-2022: updation start
		if (isset($this->request->post['mpgdpr_cookie_marketing_allow'])) {
			$data['mpgdpr_cookie_marketing_allow'] = $this->request->post['mpgdpr_cookie_marketing_allow'];
		} elseif (isset($module['mpgdpr_cookie_marketing_allow'])) {
			$data['mpgdpr_cookie_marketing_allow'] = $module['mpgdpr_cookie_marketing_allow'];
		} else {
			$data['mpgdpr_cookie_marketing_allow'] = "";
		}
		if (isset($this->request->post['mpgdpr_cookie_marketing_deny'])) {
			$data['mpgdpr_cookie_marketing_deny'] = $this->request->post['mpgdpr_cookie_marketing_deny'];
		} elseif (isset($module['mpgdpr_cookie_marketing_deny'])) {
			$data['mpgdpr_cookie_marketing_deny'] = $module['mpgdpr_cookie_marketing_deny'];
		} else {
			$data['mpgdpr_cookie_marketing_deny'] = "";
		}
		// 01-05-2022: updation end
		// 29 dec 2022 changes starts
		if (isset($this->request->post['mpgdpr_custom_js_code'])) {
			$data['mpgdpr_custom_js_code'] = $this->request->post['mpgdpr_custom_js_code'];
		} elseif (isset($module['mpgdpr_custom_js_code'])) {
			$data['mpgdpr_custom_js_code'] = $module['mpgdpr_custom_js_code'];
		} else {
			$data['mpgdpr_custom_js_code'] = "";
		}
		// 29 dec 2022 changes ends
		if (isset($this->request->post['mpgdpr_cookie_domain'])) {
			$data['mpgdpr_cookie_domain'] = $this->request->post['mpgdpr_cookie_domain'];
		} elseif (isset($module['mpgdpr_cookie_domain'])) {
			$data['mpgdpr_cookie_domain'] = $module['mpgdpr_cookie_domain'];
		} else {
			$data['mpgdpr_cookie_domain'] = '';
		}

		if (isset($this->request->post['mpgdpr_cookielang'])) {
			$data['mpgdpr_cookielang'] = $this->request->post['mpgdpr_cookielang'];
		} elseif (isset($module['mpgdpr_cookielang'])) {
			$data['mpgdpr_cookielang'] = (array) $module['mpgdpr_cookielang'];
		} else {
			$data['mpgdpr_cookielang'] = [];
		}
		// 01-05-2022: updation start
		if (isset($this->request->post['mpgdpr_langcookiepref'])) {
			$data['mpgdpr_langcookiepref'] = $this->request->post['mpgdpr_langcookiepref'];
		} elseif (isset($module['mpgdpr_langcookiepref'])) {
			$data['mpgdpr_langcookiepref'] = (array) $module['mpgdpr_langcookiepref'];
		} else {
			$data['mpgdpr_langcookiepref'] = [];
		}
		if (isset($this->request->post['mpgdpr_langrestrictprocessing'])) {
			$data['mpgdpr_langrestrictprocessing'] = $this->request->post['mpgdpr_langrestrictprocessing'];
		} elseif (isset($module['mpgdpr_langrestrictprocessing'])) {
			$data['mpgdpr_langrestrictprocessing'] = (array) $module['mpgdpr_langrestrictprocessing'];
		} else {
			$data['mpgdpr_langrestrictprocessing'] = [];
		}

		/**
		 * Acronym 	= Abbreviation
		 *
		 * rfp		= Restrict Further Processing - Enable
		 * nrfp		= Restrict Further Processing - Disable
		 * apd		= Access To Personal Data
		 * rcapd	= Resent Code - Access To Personal Data
		 * rdpd		= Request To remove Personal Data
		 * rcrdpd	= Resent Code - Request To remove Personal Data
		 * frdpd	= Final Email After Removal Of Personal Data
		 */

		$default_mpgdpr_mail_user = [
			'rfp' => 0,
			'nrfp' => 0,
			'apd' => 0,
			'rcapd' => 0,
			'rdpd' => 0,
			'rcrdpd' => 0,
			'frdpd' => 0,
		];
		$default_mpgdpr_mail_admin = [
			'rfp' => 0,
			'nrfp' => 0,
			'apd' => 0,
			'rcapd' => 0,
			'rdpd' => 0,
			'rcrdpd' => 0,
		];

		if (isset($this->request->post['mpgdpr_mail_user'])) {
			$data['mpgdpr_mail_user'] = $this->request->post['mpgdpr_mail_user'];
		} elseif (isset($module['mpgdpr_mail_user'])) {
			$data['mpgdpr_mail_user'] = (array)$module['mpgdpr_mail_user'];
		} else {
			$data['mpgdpr_mail_user'] = (!isset($module['mpgdpr_mail_user'])) ? $default_mpgdpr_mail_user : [];
		}
		if (isset($this->request->post['mpgdpr_mail_admin'])) {
			$data['mpgdpr_mail_admin'] = $this->request->post['mpgdpr_mail_admin'];
		} elseif (isset($module['mpgdpr_mail_admin'])) {
			$data['mpgdpr_mail_admin'] = (array)$module['mpgdpr_mail_admin'];
		} else {
			$data['mpgdpr_mail_admin'] = (!isset($module['mpgdpr_mail_admin'])) ? $default_mpgdpr_mail_admin : [];
		}
		if (isset($this->request->post['mpgdpr_mail_admin_email'])) {
			$data['mpgdpr_mail_admin_email'] = $this->request->post['mpgdpr_mail_admin_email'];
		} elseif (isset($module['mpgdpr_mail_admin_email'])) {
			$data['mpgdpr_mail_admin_email'] = $module['mpgdpr_mail_admin_email'];
		} else {
			$data['mpgdpr_mail_admin_email'] = $this->config->get('config_email');
		}
		if (isset($this->request->post['mpgdpr_emailtemplate'])) {
			$data['mpgdpr_emailtemplate'] = $this->request->post['mpgdpr_emailtemplate'];
		} elseif (isset($module['mpgdpr_emailtemplate'])) {
			$data['mpgdpr_emailtemplate'] = (array)$module['mpgdpr_emailtemplate'];
		} else {
			$data['mpgdpr_emailtemplate'] = [];
		}
		// 01-05-2022: updation end
		if (isset($this->request->post['mpgdpr_cbposition'])) {
			$data['mpgdpr_cbposition'] = $this->request->post['mpgdpr_cbposition'];
		} elseif (isset($module['mpgdpr_cbposition'])) {
			$data['mpgdpr_cbposition'] = $module['mpgdpr_cbposition'];
		} else {
			$data['mpgdpr_cbposition'] = '';
		}

		if (isset($this->request->post['mpgdpr_cbcolor'])) {
			$data['mpgdpr_cbcolor'] = $this->request->post['mpgdpr_cbcolor'];
		} elseif (isset($module['mpgdpr_cbcolor'])) {
			$data['mpgdpr_cbcolor'] = (array)$module['mpgdpr_cbcolor'];
		} else {
			$data['mpgdpr_cbcolor'] = [];
		}

		if (isset($this->request->post['mpgdpr_cbcss'])) {
			$data['mpgdpr_cbcss'] = $this->request->post['mpgdpr_cbcss'];
		} elseif (isset($module['mpgdpr_cbcss'])) {
			$data['mpgdpr_cbcss'] = $module['mpgdpr_cbcss'];
		} else {
			$data['mpgdpr_cbcss'] = '';
		}

		$data['cbpositions'] = [];
		$data['cbpositions'][] = [
			'value' => 'bottom-left',
			'text' =>$this->language->get('text_cbposition_left')
		];
		$data['cbpositions'][] = [
			'value' => 'bottom-right',
			'text' =>$this->language->get('text_cbposition_right')
		];
		$data['cbpositions'][] = [
			'value' => 'static',
			'text' =>$this->language->get('text_cbposition_static')
		];
		$data['cbpositions'][] = [
			'value' => 'top',
			'text' =>$this->language->get('text_cbposition_top')
		];
		$data['cbpositions'][] = [
			'value' => 'bottom',
			'text' =>$this->language->get('text_cbposition_bottom')
		];

		$data['cbinitials'] = [];
		$data['cbinitials'][] = [
			'value' => 'cookieanalytic_block',
			'text' =>$this->language->get('text_cookie_analytic_block')
		];
		$data['cbinitials'][] = [
			'value' => 'cookiemarketing_block',
			'text' =>$this->language->get('text_cookie_marketing_block')
		];
		$data['cbinitials'][] = [
			'value' => 'cookieanalyticmarketing_block',
			'text' =>$this->language->get('text_cookie_analyticmarketing_block')
		];
		$data['cbinitials'][] = [
			'value' => 'idel',
			'text' =>$this->language->get('text_cookie_idel')
		];

		$data['cbactions_close'] = [];
		$data['cbactions_close'][] = [
			'value' => 'cookieanalytic_block',
			'text' =>$this->language->get('text_cookie_analytic_block')
		];
		$data['cbactions_close'][] = [
			'value' => 'cookiemarketing_block',
			'text' =>$this->language->get('text_cookie_marketing_block')
		];
		$data['cbactions_close'][] = [
			'value' => 'cookieanalyticmarketing_block',
			'text' =>$this->language->get('text_cookie_analyticmarketing_block')
		];
		$data['cbactions_close'][] = [
			'value' => 'idel',
			'text' =>$this->language->get('text_cookie_idel')
		];


		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->viewLoad($this->extension_path . 'module/mpgdpr', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', $this->extension_path . 'module/mpgdpr')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	// 29 dec 2022 changes starts

	protected function accessValidate() {
		if (!$this->user->hasPermission('access', $this->extension_path . 'module/mpgdpr')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	// model like functions starts
	public function areEventsDisable($code) {
		$disable_events = [];

		// no events for oc version 2.2.0.0 or below. Only OCMOD.
		if (VERSION <= '2.2.0.0') {
			return $disable_events;
		}

		// get events from db
		$query = $this->db->query("SELECT DISTINCT `event_id` FROM `" . DB_PREFIX . "event` WHERE `code`='" . $this->db->escape($code) . "' AND `status`=0");

		foreach ($query->rows as $key => $value) {
			$disable_events[] = $value['event_id'];
		}

		return $disable_events;
	}

	public function removeEventsByCode($code) {
		// no events for oc version 2.2.0.0 or below. Only OCMOD.
		if (VERSION <= '2.2.0.0') {
			return;
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code`='" . $this->db->escape($code) . "'");
	}

	public function removeEvent($event_id) {
		// no events for oc version 2.2.0.0 or below. Only OCMOD.
		if (VERSION <= '2.2.0.0') {
			return;
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `event_id`='" . (int)$event_id . "'");
	}

	public function createEvents($events, $code) {

		// no events for oc version 2.2.0.0 or below. Only OCMOD.
		if (VERSION <= '2.2.0.0') {
			return;
		}

		$this->load->model($this->model_file['extension/event']['path']);
		$defaults = [
			'status' => 1,
			'sort_order' => 0,
			'description' => '',
		];

		// get events from db
		$query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "event` WHERE `code`='" . $this->db->escape($code) . "'");

		$db_events = [];
		foreach ($query->rows as $key => $value) {
			$triact = "{$value['trigger']}==={$value['action']}";
			$db_events[] = $triact;
		}

		$removed_events_in_db = [];
		$trion = [];
		foreach ($events as $key => $event) {
			$triact = "{$event['trigger']}==={$event['action']}";
			$trion[] = $triact;
			if (!in_array($triact, $db_events)) {
				$removed_events_in_db[] = $event;
			}
		}

		// non required events present in database.
		$non_required_events = [];
		foreach ($query->rows as $key => $value) {
			$triact = "{$value['trigger']}==={$value['action']}";
			if (!in_array($triact, $trion)) {
				$non_required_events[] = $value;
			}
		}

		// delete non required events from database
		foreach ($non_required_events as $key => $value) {
			$this->removeEvent($value['event_id']);
		}

		foreach ($removed_events_in_db as $event) {

			// add default keys in array
			foreach ($defaults as $key => $value) {
				if (!isset($event[$key])) {
					$event[$key] = $value;
				}
			}

			$this->{$this->model_file['extension/event']['obj']}->addEvent($code, $event['trigger'], $event['action'], $event['status'], $event['sort_order']);
		}
	}
	// model like functions ends

	// event starts


	// 'trigger' => 'admin/view/common/column_left/before',
	public function getMenu(&$route, &$data, &$code) {

		$mpgdpr = [];
		$this->load->language($this->extension_path . 'mpgdpr/menu_mpgdpr');
		if ($this->user->hasPermission('access', $this->extension_path . 'module/mpgdpr')) {
			$mpgdpr[] = [
				'name'	   => $this->labelEnableDisable((int)$this->config->get('mpgdpr_status')) . ' ' . $this->language->get('text_mpgdpr'),
				'href'     => $this->url->link($this->extension_path . 'module/mpgdpr', $this->token.'=' . $this->session->data[$this->token], true),
				'children' => []
			];
		}
		if ($this->user->hasPermission('access', $this->extension_path . 'mpgdpr/requestlist')) {
			$mpgdpr[] = [
				'name'	   => $this->language->get('text_mpgdpr_requestlist'),
				'href'     => $this->url->link($this->extension_path . 'mpgdpr/requestlist', $this->token.'=' . $this->session->data[$this->token], true),
				'children' => []
			];
		}
		if ($this->user->hasPermission('access', $this->extension_path . 'mpgdpr/policyacceptance')) {
			$mpgdpr[] = [
				'name'	   => $this->language->get('text_mpgdpr_policyacceptance'),
				'href'     => $this->url->link($this->extension_path . 'mpgdpr/policyacceptance', $this->token.'=' . $this->session->data[$this->token], true),
				'children' => []
			];
		}
		if ($this->user->hasPermission('access', $this->extension_path . 'mpgdpr/requestanonymouse')) {
			$mpgdpr[] = [
				'name'	   => $this->language->get('text_mpgdpr_requestanonymouse'),
				'href'     => $this->url->link($this->extension_path . 'mpgdpr/requestanonymouse', $this->token.'=' . $this->session->data[$this->token], true),
				'children' => []
			];
		}
		if ($this->user->hasPermission('access', $this->extension_path . 'mpgdpr/requestaccessdata')) {
			$mpgdpr[] = [
				'name'	   => $this->language->get('text_mpgdpr_requestaccessdata'),
				'href'     => $this->url->link($this->extension_path . 'mpgdpr/requestaccessdata', $this->token.'=' . $this->session->data[$this->token], true),
				'children' => []
			];
		}

		$menu = [];

		if ($mpgdpr && $this->moduleIsInstalled('mpgdpr')) {
			$menu = [
				'id'       => 'mp-gdpr',
				'icon'	   => 'fa-gavel',
				'name'	   => $this->language->get('text_menu_mpgdpr'),
				'href'     => '',
				'children' => $mpgdpr
			];
		}

		if ($menu) {

			$index = 3;
			foreach ($data['menus'] as $key => $value) {
				if ($value['id'] == 'menu-design') {
					$index = $key;
				}
			}

			array_splice($data['menus'], $index, 0, [$menu]);
		}
	}

	// add restrict access alert at customer page start
	// 'trigger' => 'admin/view/customer/customer_list/after',
	public function customerListProcessRestrict(&$route, &$data, &$output) {
		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/
		$this->load->language($this->extension_path . 'mpgdpr/mpgdpr_common');
		$data['text_mpgdpr_processrestrict'] = $this->language->get('text_mpgdpr_processrestrict');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
		/*mpgdpr ends*/
		/*end gdpr 28-07-2018*/

		foreach ($data['customers'] as $key => $result) {
			$result['mpgdpr_processrestrict'] = $data['customers'][$key]['mpgdpr_processrestrict'] = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getRestrictProcessing($result['customer_id']);

			if ($result['mpgdpr_processrestrict']) {
				$find = '<td class="text-left">' . $result['email'] . '</td>';
				$replace = '<td class="text-left alert-danger">' . $result['email'] . ' - ' . $data['text_mpgdpr_processrestrict'] . '</td>';

				$output = str_replace($find, $replace, $output);

			}

		}

	}
	// add restrict access alert at customer page end

	// event ends
	// 29 dec 2022 changes ends
}