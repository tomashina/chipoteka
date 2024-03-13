<?php
class ControllerExtensionMpGdprAccountRestriction extends \Mpgdpr\Controller {
	use \Mpgdpr\trait_mpgdpr_catalog;

	public function __construct($registry) {
		parent :: __construct($registry);
		$this->igniteTraitMpgdprCatalog($registry);
	}

	private $error = [];
	public function index() {
		if ($this->config->get('mpgdpr_login_gdprforms') && !$this->customer->getId()) {
			$this->session->data['redirect'] = $this->url->link($this->extension_path . 'mpgdpr/account/restriction', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		if (!$this->config->get('mpgdpr_status')) {
			return new Action('error/not_found');
		}

		$this->load->language($this->extension_path . 'mpgdpr/restriction');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');


		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			$customer_id = $this->customer->getId();
			// if customer is not logged in. then fetch customer_id from email.
			if (!$customer_id) {
				$customer_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerIdFromEmail($this->request->post['email']);
			}

			$restriction_info = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getRestrictProcessing($customer_id);
			// add restriction record
			$request_data = [
				'customer_id' => $customer_id,
				'email' => $this->request->post['email'],
				'status' => $this->request->post['mpgdpr_restrict'],
			];

			if ($restriction_info) {
				/*13 sep 2019 gdpr session starts*/
				$mpgdpr_restrict_processing_id = $restriction_info['mpgdpr_restrict_processing_id'];
				$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->editRestrictProcessing($request_data);
				/*13 sep 2019 gdpr session ends*/
			} else {
				$mpgdpr_restrict_processing_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRestrictProcessing($request_data);
			}

			/*13 sep 2019 gdpr session starts*/
			// Add to request log
			$request_data = [
				'customer_id' => $customer_id,
				'email' => $this->request->post['email'],
				'date' => date('Y-m-d H:i:s'),
				'custom_string' => sprintf($this->language->get('text_gdpr_request_custom_msg'), ($this->request->post['mpgdpr_restrict'] ? $this->language->get('text_yes') : $this->language->get('text_no')) ),
			];
			// 01-05-2022: updation start
			$mpgdpr_requestlist_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEREQUESTRESSTRICTDATAPROCESSING, $request_data);
			// 01-05-2022: updation end
			/*13 sep 2019 gdpr session ends*/
			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr', '', true));

		}

		$this->document->setTitle($this->language->get('heading_title'));

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
			'text' => $this->language->get('text_gdpr_restriction'),
			'href' => $this->url->link($this->extension_path . 'mpgdpr/account/restriction', '', true)
		];

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
		$data['text_restriction'] = $this->language->get('text_restriction');
		// 01-05-2022: updation start
		$data['text_restrict_processing_alert'] = $this->language->get('text_restrict_processing_alert');
		// 01-05-2022: updation end
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');

		$data['entry_email'] = $this->language->get('entry_email');
		$data['entry_restrict'] = $this->language->get('entry_restrict');

		$data['button_continue'] = $this->language->get('button_continue');
		$data['button_back'] = $this->language->get('button_back');

		$data['back'] = $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr', '', true);
		$data['action'] = $this->url->link($this->extension_path . 'mpgdpr/account/restriction', '', true);

		if (isset($this->request->post['mpgdpr_restrict'])) {
			$data['mpgdpr_restrict'] = $this->request->post['mpgdpr_restrict'];
		} elseif ($this->customer->getId()) {
			$restriction_info = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getRestrictProcessing($this->customer->getId());
			if ($restriction_info) {
				$data['mpgdpr_restrict'] = $restriction_info['status'];
			} else {
				$data['mpgdpr_restrict'] = 0;
			}
		} else {
			$data['mpgdpr_restrict'] = 0;
		}

		// 01-05-2022: updation start
		$restrictprocessing = $this->config->get('mpgdpr_langrestrictprocessing');
		$restrict_processing = [];
		if (isset($restrictprocessing[(int)$this->config->get('config_language_id')])) {
			$restrict_processing = $restrictprocessing[(int)$this->config->get('config_language_id')];
		}
		if (!empty($restrict_processing['alert'])) {
			$data['text_restrict_processing_alert'] = $restrict_processing['alert'];
		}
		// 01-05-2022: updation end

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

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$data['extension_path'] = $this->extension_path;

		$this->response->setOutput($this->viewLoad($this->extension_path . 'mpgdpr/account/restriction', $data));
	}

	private function validate() {
		if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
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