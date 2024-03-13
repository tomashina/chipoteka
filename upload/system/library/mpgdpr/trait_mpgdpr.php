<?php
namespace Mpgdpr;
// use in admin only
trait trait_mpgdpr {

	private $token = 'token';
	private $ssl = true;
	private $extension_page_path = 'extension/extension';
	private $extension_path = 'extension/';
	private $extension_model = 'extension_';
	private $extension_prefix = ['module' => '', 'payment' => '', 'shipping' => '', 'total' =>  '', 'captcha' =>  ''];
	private $model_file = [
		'extension/extension' => [
			'path' => 'extension/extension',
			'obj' => 'model_extension_extension',
		],
		'extension/module' => [
			'path' => 'extension/module',
			'obj' => 'model_extension_module',
		],
		'customer/custom_field' => [
			'path' => 'customer/custom_field',
			'obj' => 'model_customer_custom_field',
		],
		'extension/event' => [
			'path' => 'extension/event',
			'obj' => 'model_extension_event',
		],
	];
	private $affiliate_show = true;
	public function igniteTraitMpGdpr($registry) {
		if (VERSION < '2.2.0.0') {
			$this->ssl = 'ssl';
		}

		if (VERSION <= '2.2.0.0') {
			$this->extension_path = '';
			$this->extension_model = '';
		}
		if (VERSION < '2.0.3.1') {
			$this->model_file['customer/custom_field'] = [
				'path' => 'sale/custom_field',
				'obj' => 'model_sale_custom_field',
			];
		}

		if (VERSION >= '3.0.0.0') {
			$this->affiliate_show = false;
			$this->token = 'user_token';
			$this->extension_page_path = 'marketplace/extension';

			$this->extension_prefix = [
				'module' => 'module_',
				'payment' => 'payment_',
				'shipping' => 'shipping_',
				'total' => 'total_',
				'captcha' => 'captcha_',
			];
			$this->model_file['extension/extension'] = [
				'path' => 'setting/extension',
				'obj' => 'model_setting_extension',
			];
			$this->model_file['extension/module'] = [
				'path' => 'setting/module',
				'obj' => 'model_setting_module',
			];
			$this->model_file['extension/event'] = [
				'path' => 'setting/event',
				'obj' => 'model_setting_event',
			];
		}

	}


	protected function breadcrumbs(&$data) {
		$this->load->language($this->extension_path . 'mpgdpr/menu_mpgdpr', 'menu_mpgdpr');

		if (VERSION >= '3.0.0.0') {
			$text_extension = $this->language->get('menu_mpgdpr')->get('text_extension');
			$heading_mpgdpr = $this->language->get('menu_mpgdpr')->get('heading_mpgdpr');
		} else {
			$text_extension = $this->language->get('text_extension');
			$heading_mpgdpr = $this->language->get('heading_mpgdpr');

		}

		$data['breadcrumbs'][] = [
			'text' => $text_extension,
			'href' => $this->url->link($this->extension_page_path, $this->token.'=' . $this->session->data[$this->token], true)
		];

		if ($this->request->get['route'] != $this->extension_path . 'module/mpgdpr') {
			$data['breadcrumbs'][] = [
				'text' => $heading_mpgdpr,
				'href' => $this->url->link($this->extension_path . 'module/mpgdpr', $this->token.'=' . $this->session->data[$this->token], true)
			];
		}

	}

	public function customerModelObj() {
		if (VERSION < '2.2.0.0') {
			$this->load->model('sale/customer');
			$model_customer = 'model_sale_customer';
		} else {
			$this->load->model('customer/customer');
			$model_customer = 'model_customer_customer';
		}
		return $model_customer;
	}
	public function customerGroupModelObj() {
		if (VERSION < '2.2.0.0') {
			$this->load->model('sale/customer_group');
			$model_customer_group = 'model_sale_customer_group';
		} else {
			$this->load->model('customer/customer_group');
			$model_customer_group = 'model_customer_customer_group';
		}
		return $model_customer_group;
	}

	public function getCustomerGroups() {
		return $this->{$this->customerGroupModelObj()}->getCustomerGroups();
	}

	public function getLanguages() {
		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();

		if (VERSION >= '2.2.0.0') {
			foreach ($languages as &$language) {
				$language['lang_flag'] = 'language/'.$language['code'].'/'.$language['code'].'.png';
			}
		} else {
			foreach ($languages as &$language) {
				$language['lang_flag'] = 'view/image/flags/'.$language['image'].'';
			}
		}
		return $languages;
	}

	public function viewLoad($path, &$data, $twig=false) {
		if (VERSION >= '3.0.0.0' && !$twig) {
			$old_template = $this->config->get('template_engine');
			$this->config->set('template_engine', 'template');
		}
		$view = $this->load->view($this->path($path), $data);
		if (VERSION >= '3.0.0.0' && !$twig) {
			$this->config->set('template_engine', $old_template);
		}
		return $view;
	}

	public function path($path) {
		$path_info = pathinfo($path);

		$npath = $path_info['dirname'] . '/'. $path_info['filename'];
		if (VERSION <= '2.3.0.2') {
			$npath.= '.tpl';
		}
		return $npath;
	}

	public function textEditor(&$d) {
		$d['summernote'] = '';
		$data = [];
		return $this->viewLoad($this->extension_path . 'mpgdpr/texteditor', $data);
	}

	public function installDb() {

	}

	public function labelEnableDisable($status) {

		$text = $this->language->get('text_disabled');
		$label = 'danger';
		if ($status) {
			$text = $this->language->get('text_enabled');
			$label = 'success';
		}
		return '<i class="fa fa-circle text-'. $label .'"></i>';
	}

	// explicit code for 2x, lower than 2.3x versions only.
	// call using ocmod

	public function getAllLanguageMpgdpr(&$data) {
		// method comes through ocmod.
		if (method_exists($this->language, 'getAllLanguageMpgdpr')) {
			$all = $this->language->getAllLanguageMpgdpr();
			foreach ($all as $key => $value) {
				$data[$key] = $value;
			}
		}
		// from oc2.3x we have language all method.
		if (method_exists($this->language, 'all')) {
			$all = $this->language->all();
			foreach ($all as $key => $value) {
				$data[$key] = $value;
			}
		}
	}

}


if (!function_exists('token')) {
	function token($length = 32) {
		// Create random token
		$string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$max = strlen($string) - 1;

		$token = '';

		for ($i = 0; $i < $length; $i++) {
			$token .= $string[mt_rand(0, $max)];
		}
		return $token;
	}
}
