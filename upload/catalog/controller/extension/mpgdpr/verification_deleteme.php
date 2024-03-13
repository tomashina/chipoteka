<?php

class ControllerExtensionMpGdprVerificationDeleteMe extends \Mpgdpr\Controller {
	use \Mpgdpr\trait_mpgdpr_catalog;

	public function __construct($registry) {
		parent :: __construct($registry);
		$this->igniteTraitMpgdprCatalog($registry);
	}

	private $error = [];
	public function index() {
		if (!$this->config->get('mpgdpr_status')) {
			return new Action('error/not_found');
		}
		$this->load->language($this->extension_path . 'mpgdpr/verification_deleteme');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

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
			'text' => $this->language->get('text_gdpr_datarequest'),
			'href' => $this->url->link($this->extension_path . 'mpgdpr/verification_deleteme', '', true)
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

		$data['code'] = '';

		$data['heading_title'] = $this->language->get('heading_title');

		$data['entry_code'] = $this->language->get('entry_code');

		$data['text_message'] = $this->language->get('text_verifycode');

		$data['button_continue'] = $this->language->get('button_continue');
		$data['button_verify'] = $this->language->get('button_verify');

		$data['action'] = $this->url->link('common/home', '', true);


		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$data['extension_path'] = $this->extension_path;

		$this->response->setOutput($this->load->view($this->extension_path . 'mpgdpr/verification_deleteme', $data));
	}

	public function verification() {
		$json = [];
		if (!$this->config->get('mpgdpr_status')) {
			$this->response->setOutput(json_encode([]));
			$this->response->output();
			exit;
		}
		$this->load->language($this->extension_path . 'mpgdpr/verification_deleteme');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

		if (!isset($this->request->get['o']) || (isset($this->request->get['o']) && $this->request->get['o'] != 1) ) {
			$json['error'] = $this->language->get('error_invalid');
		}

		if (empty($this->request->post['code'])) {
			$json['code_empty'] = $this->language->get('error_code_empty');
		}

		if (!$json) {
			// validate code here
			$request_info = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getDeleteMeRequestByCode($this->request->post['code']);


			if (empty($request_info)) {
				$json['error'] = $this->language->get('error_code_invalid');
			}
		}
		if (!$json) {
			// code found, lets check if expired or not. when status is awating confirmation
			$today = date('Y-m-d H:i:s');
			// 01-05-2022: updation start
			if (strtotime($today) > strtotime($request_info['expire_on']) && $request_info['status'] == \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_AWATING ) {

				// expire the request as timeout
				$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->updateDeleteMeRequestStatus($request_info['mpgdpr_deleteme_id'], \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_EXPIRE);

				$json['error'] = $this->language->get('error_code_expire');
			}
			// 01-05-2022: updation end
		}

		if (!$json) {
			// code found, lets check if status awating or something else
			// 01-05-2022: updation start
			if ($request_info['status'] == \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_AWATING ) {

				// complete the verification here
				$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->updateDeleteMeRequestStatus($request_info['mpgdpr_deleteme_id'], \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_CONFIRMED);

				$json['success'] = $this->language->get('text_verify_success');
			}
			// let check if what is the status of request now and response accordingly
			// if request status is expired
			if ($request_info['status'] == \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_EXPIRE ) {
				$json['error'] = $this->language->get('error_code_expire');
			}
			// if request status is confirmed
			if ($request_info['status'] == \Mpgdpr\Mpgdpr :: REQUESTANONYMOUSE_CONFIRMED ) {
				$json['error'] = $this->language->get('text_verified');
			}
			// 01-05-2022: updation end
			// here we check if json has respone or not. if not then say request unknow
			if (!$json) {
				$json['error'] = $this->language->get('text_request_unknown');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// 01-05-2022: updation start
	public function resentCode() {
		$json = [];
		if (!$this->config->get('mpgdpr_status') || !$this->customer->getId()) {
			$this->response->setOutput(json_encode([]));
			$this->response->output();
			exit;
		}
		$this->load->language($this->extension_path . 'mpgdpr/verification_deleteme');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

		if (!isset($this->request->get['o']) || (isset($this->request->get['o']) && $this->request->get['o'] != 1) ) {
			$json['error'] = $this->language->get('error_invalid');
		}

		if (!isset($this->request->get['mpgdpr_deleteme_id']) || empty($this->request->get['mpgdpr_deleteme_id'])) {
			$json['error'] = $this->language->get('error_invalid');
		}

		if (!$json) {
			$success = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->resentDeleteMeRequestCode((int)$this->request->get['mpgdpr_deleteme_id'], $this->customer->getEmail());
			if ($success) {
				$json['success'] = $this->language->get('text_success_resentdeleterequestcode');
			} else {
				$json['error'] = $this->language->get('error_failed');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	// 01-05-2022: updation end
}