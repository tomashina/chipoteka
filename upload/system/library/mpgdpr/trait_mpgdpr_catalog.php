<?php
namespace Mpgdpr;
// use in admin only
trait trait_mpgdpr_catalog {

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
		'extension/event' => [
			'path' => 'extension/event',
			'obj' => 'model_extension_event',
		],
	];
	private $affiliate_show = true;
	public function igniteTraitMpgdprCatalog($registry) {

		if (VERSION <= '2.2.0.0') {
			$this->extension_path = '';
			$this->extension_model = '';
		}

		if (VERSION >= '3.0.0.0') {
			$this->affiliate_show = false;
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

	public function viewLoad($path, &$data) {

		if (VERSION >= '2.2.0.0') {
			$view = $this->load->view($path, $data);
		} else {
			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/' . $this->path($path))) {
				$view = $this->load->view($this->config->get('config_template') . '/template/' . $this->path($path), $data);
			 } else {
				$view = $this->load->view('default/template/' . $this->path($path), $data);
			 }
		}

		return $view;
	}

	public function path($path) {
		$path_info = pathinfo($path);

		$npath = $path_info['dirname'] . '/'. $path_info['filename'];
		if (VERSION < '2.2.0.0') {
			$npath.= '.tpl';
		}
		return $npath;
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
		return '<span class="text-'. $label .'">' . $text . '</span>';
	}

	// explicit code for 2x, lower than 2.3x versions only.
	// call using ocmod

	private function getAllLanguageMpgdpr(&$data) {
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
