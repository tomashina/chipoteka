<?php
class ControllerExtensionModuleGoogleLogin extends Controller {
	const TRANSACTION_TTL = 600;

	public function index() {
		$this->load->language('extension/module/google_login');
		$flow = $this->getRequestedFlow();

		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account', '', true));
		}

		if (!$this->isEnabled()) {
			$this->fail($this->language->get('error_disabled'), '', $flow);
		}

		try {
			$state = $this->randomToken(32);
			$nonce = $this->randomToken(32);
			$code_verifier = $this->randomToken(64);
			$code_challenge = $this->base64UrlEncode(hash('sha256', $code_verifier, true));
		} catch (\Exception $exception) {
			$this->fail($this->language->get('error_service'), 'Unable to create OAuth security tokens: ' . $exception->getMessage());
		}

		$redirect = '';

		if (!empty($this->request->get['redirect'])) {
			$candidate = html_entity_decode($this->request->get['redirect'], ENT_QUOTES, 'UTF-8');

			if ($this->isAllowedRedirect($candidate)) {
				$redirect = $candidate;
			}
		}

		$this->session->data['google_login_transaction'] = array(
			'state'         => $state,
			'nonce'         => $nonce,
			'code_verifier' => $code_verifier,
			'flow'          => $flow,
			'redirect'      => $redirect,
			'created_at'    => time()
		);

		$this->load->library('google/oidc');

		$authorization_url = $this->oidc->getAuthorizationUrl(
			trim($this->config->get('module_google_login_client_id')),
			$this->getCallbackUrl(),
			$state,
			$nonce,
			$code_challenge
		);

		$this->response->redirect($authorization_url);
	}

	public function callback() {
		$this->load->language('extension/module/google_login');

		if ($this->customer->isLogged()) {
			unset($this->session->data['google_login_transaction']);
			$this->response->redirect($this->url->link('account/account', '', true));
		}

		$transaction = isset($this->session->data['google_login_transaction']) ? $this->session->data['google_login_transaction'] : array();
		unset($this->session->data['google_login_transaction']);
		$flow = isset($transaction['flow']) && $transaction['flow'] === 'register' ? 'register' : 'login';

		if (!$this->isEnabled()) {
			$this->fail($this->language->get('error_disabled'), '', $flow);
		}

		if (!$this->isValidTransaction($transaction)) {
			$this->fail($this->language->get('error_expired'), '', $flow);
		}

		$state = isset($this->request->get['state']) ? (string)$this->request->get['state'] : '';

		if ($state === '' || !hash_equals($transaction['state'], $state)) {
			$this->fail($this->language->get('error_invalid_request'), 'OAuth state validation failed.', $flow);
		}

		if (!empty($this->request->get['error'])) {
			if ($this->request->get['error'] === 'access_denied') {
				$this->fail($this->language->get('error_cancelled'), '', $flow);
			}

			$this->fail($this->language->get('error_service'), 'Google returned OAuth error: ' . preg_replace('/[^a-z0-9_\-]/i', '', $this->request->get['error']), $flow);
		}

		$code = isset($this->request->get['code']) ? (string)$this->request->get['code'] : '';

		if ($code === '' || strlen($code) > 4096) {
			$this->fail($this->language->get('error_invalid_request'), 'Authorization code is missing or invalid.', $flow);
		}

		try {
			$this->load->library('google/oidc');

			$client_id = trim($this->config->get('module_google_login_client_id'));
			$token_response = $this->oidc->exchangeAuthorizationCode(
				$client_id,
				trim($this->config->get('module_google_login_client_secret')),
				$this->getCallbackUrl(),
				$code,
				$transaction['code_verifier']
			);

			$claims = $this->oidc->verifyIdToken($token_response['id_token'], $client_id, $transaction['nonce']);
		} catch (\Exception $exception) {
			$this->fail($this->language->get('error_service'), $exception->getMessage(), $flow);
		}

		$email = utf8_strtolower(trim($claims['email']));

		if (!$this->isAuthoritativeGoogleEmail($email, $claims)) {
			$this->fail($this->language->get('error_email_authority'), '', $flow);
		}

		$this->load->model('account/customer');
		$customer_info = $this->model_account_customer->getCustomerByEmail($email);

		if (!$customer_info) {
			if ($flow === 'register') {
				$this->startRegistration($email, $claims);
			}

			$this->fail($this->language->get('error_customer_missing'));
		}

		if (empty($customer_info['status'])) {
			$this->fail($this->language->get('error_customer_disabled'), '', $flow);
		}

		if (!$this->customer->login($customer_info['email'], '', true)) {
			$this->fail($this->language->get('error_login'), '', $flow);
		}

		$this->model_account_customer->deleteLoginAttempts($customer_info['email']);
		$this->completeLogin();

		if (!empty($transaction['redirect']) && $this->isAllowedRedirect($transaction['redirect'])) {
			$this->response->redirect($transaction['redirect']);
		}

		if ($this->customer->getGroupId() > 2) {
			$this->response->redirect($this->url->link('common/home', '', true));
		}

		$this->response->redirect($this->url->link('account/account', '', true));
	}

	private function completeLogin() {
		unset($this->session->data['google_registration']);
		unset($this->session->data['guest']);

		$this->load->model('account/address');

		if ($this->config->get('config_tax_customer') == 'payment') {
			$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
		}

		if ($this->config->get('config_tax_customer') == 'shipping') {
			$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
		}

		if (isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist'])) {
			$this->load->model('account/wishlist');

			foreach ($this->session->data['wishlist'] as $key => $product_id) {
				$this->model_account_wishlist->addWishlist($product_id);
				unset($this->session->data['wishlist'][$key]);
			}
		}
	}

	private function isEnabled() {
		return $this->config->get('module_google_login_status')
			&& trim((string)$this->config->get('module_google_login_client_id')) !== ''
			&& trim((string)$this->config->get('module_google_login_client_secret')) !== '';
	}

	private function isValidTransaction(array $transaction) {
		return isset($transaction['state'], $transaction['nonce'], $transaction['code_verifier'], $transaction['flow'], $transaction['created_at'])
			&& is_string($transaction['state'])
			&& is_string($transaction['nonce'])
			&& is_string($transaction['code_verifier'])
			&& in_array($transaction['flow'], array('login', 'register'), true)
			&& is_numeric($transaction['created_at'])
			&& (int)$transaction['created_at'] >= (time() - self::TRANSACTION_TTL)
			&& (int)$transaction['created_at'] <= (time() + 60);
	}

	private function isAuthoritativeGoogleEmail($email, array $claims) {
		$domain = substr(strrchr($email, '@'), 1);

		if ($domain === 'gmail.com' || $domain === 'googlemail.com') {
			return true;
		}

		return !empty($claims['hd']) && is_string($claims['hd']);
	}

	private function getRequestedFlow() {
		return isset($this->request->get['flow']) && $this->request->get['flow'] === 'register' ? 'register' : 'login';
	}

	private function startRegistration($email, array $claims) {
		$firstname = isset($claims['given_name']) && is_string($claims['given_name']) ? trim($claims['given_name']) : '';
		$lastname = isset($claims['family_name']) && is_string($claims['family_name']) ? trim($claims['family_name']) : '';

		if ($firstname === '' && !empty($claims['name']) && is_string($claims['name'])) {
			$name_parts = preg_split('/\s+/u', trim($claims['name']), 2);
			$firstname = isset($name_parts[0]) ? $name_parts[0] : '';
			$lastname = isset($name_parts[1]) ? $name_parts[1] : $lastname;
		}

		$this->session->data['google_registration'] = array(
			'email'      => $email,
			'firstname'  => $firstname,
			'lastname'   => $lastname,
			'sub'        => $claims['sub'],
			'created_at' => time()
		);

		$this->session->data['success'] = $this->language->get('text_registration_verified');
		$this->response->redirect($this->url->link('account/register', '', true));
	}

	private function isAllowedRedirect($url) {
		if (!is_string($url) || $url === '') {
			return false;
		}

		$url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

		if ($url === html_entity_decode($this->url->link('account/logout', '', true), ENT_QUOTES, 'UTF-8')) {
			return false;
		}

		$target = parse_url($url);

		if (!$target || empty($target['scheme']) || empty($target['host']) || !in_array(strtolower($target['scheme']), array('http', 'https'), true)) {
			return false;
		}

		foreach (array($this->config->get('config_url'), $this->config->get('config_ssl')) as $store_url) {
			$allowed = parse_url($store_url);

			if ($allowed && !empty($allowed['host']) && strtolower($target['host']) === strtolower($allowed['host'])) {
				$target_port = isset($target['port']) ? (int)$target['port'] : null;
				$allowed_port = isset($allowed['port']) ? (int)$allowed['port'] : null;

				if ($target_port === $allowed_port) {
					return true;
				}
			}
		}

		return false;
	}

	private function getCallbackUrl() {
		return html_entity_decode($this->url->link('extension/module/google_login/callback', '', true), ENT_QUOTES, 'UTF-8');
	}

	private function randomToken($bytes) {
		return $this->base64UrlEncode(random_bytes($bytes));
	}

	private function base64UrlEncode($value) {
		return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
	}

	private function fail($message, $log_message = '', $flow = 'login') {
		if ($log_message !== '') {
			$this->log->write('Google Login: ' . $log_message);
		}

		$this->session->data['error'] = $message;
		$route = $flow === 'register' ? 'account/register' : 'account/login';
		$this->response->redirect($this->url->link($route, '', true));
	}
}
