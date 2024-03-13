<?php

class ControllerExtensionMpGdprMpGdpr extends \Mpgdpr\Controller {
	use \Mpgdpr\trait_mpgdpr_catalog;

	public function __construct($registry) {
		parent :: __construct($registry);
		$this->igniteTraitMpgdprCatalog($registry);
	}

	public function acceptanceOfPp() {
		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_cbstatus') && $this->config->get('mpgdpr_cbpolicy') && $this->config->get('mpgdpr_cbpptrack')) {

			$data['cbpolicy_page'] = $this->config->get('mpgdpr_cbpolicy_page');
			if (!$data['cbpolicy_page']) {
				$data['cbpolicy_page'] = $this->config->get('config_account_id');
			}

			if ($data['cbpolicy_page']) {
				$this->load->language($this->extension_path . 'mpgdpr/gdpr');
				$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
				$this->load->model('catalog/information');
				$information_info = $this->model_catalog_information->getInformation($data['cbpolicy_page']);
				if ($information_info) {
					$insert_data = [
						'customer_id' => $this->customer->getId(),
						'policy_id' => $information_info['information_id'],
						// 01-05-2022: updation start
						'policy_title' => $this->config->get('mpgdpr_policy_data') ? $information_info['title'] : '',
						'policy_description' => $this->config->get('mpgdpr_policy_data') ? $information_info['description'] : '',
						// 01-05-2022: updation end
					];

					/*13 sep 2019 gdpr session starts*/
					// 01-05-2022: updation start
					$mpgdpr_policyacceptance_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addPolicyAcceptance(\Mpgdpr\Mpgdpr :: CODEPOLICYACCEPTCOOKIECONSENT, $insert_data);
					// 01-05-2022: updation end
					// Add to request log
					$request_data = [
						'customer_id' => $this->customer->getId(),
						'email' => $this->customer->getEmail(),
						'date' => date('Y-m-d H:i:s'),
						'custom_string' => sprintf($this->language->get('text_gdpr_policyacceptcookieconsent_custom_msg'), $mpgdpr_policyacceptance_id ),
					];
					// 01-05-2022: updation start
					$mpgdpr_requestlist_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEPOLICYACCEPTCOOKIECONSENT, $request_data);
					// 01-05-2022: updation end
					/*13 sep 2019 gdpr session ends*/
				}
			}
		}
	}

	// 01-05-2022: updation start

	// return true of not allow analytic cookie, false if allowed or neutral (no action taken & default setting to allow cookies)
	protected function analyticOptInOut() {
		$analytic = false;

		if (!isset($this->request->cookie['mpcookie_preferencesdisable']) && isset($this->request->cookie['cookieconsent_status']) && 'allow' === $this->request->cookie['cookieconsent_status']) {
			$analytic = true;
		}


		if ((isset($this->request->cookie['mpcookie_preferencesdisable']) && !in_array('analytic', str_replace("\r", "", explode(",", $this->request->cookie['mpcookie_preferencesdisable'])))) && (isset($this->request->cookie['cookieconsent_status']) && 'allow' === $this->request->cookie['cookieconsent_status'])) {
			$analytic = true;
		}

		if (!isset($this->request->cookie['mpcookie_preferencesdisable']) && !isset($this->request->cookie['cookieconsent_status'])) {

			if ($this->config->get('mpgdpr_cbinitial') === 'cookieanalytic_block' || $this->config->get('mpgdpr_cbinitial') === 'cookieanalyticmarketing_block') {
				$analytic = true;
			}

		}

		// this.config.get('mpgdpr_cbinitial')
		// cookieanalytic_block
		// cookiemarketing_block
		// cookieanalyticmarketing_block
		// idel
		// echo "analytic : " . ($analytic ? "allow" : "deny");
		return $analytic;
	}

	// return true of not allow marketing cookie, false if allowed or neutral (no action taken & default setting to allow cookies)
	protected function marketingOptInOut() {
		$marketing = false;

		if (!isset($this->request->cookie['mpcookie_preferencesdisable']) && isset($this->request->cookie['cookieconsent_status']) && 'allow' === $this->request->cookie['cookieconsent_status']) {
			$marketing = true;
		}


		if ((isset($this->request->cookie['mpcookie_preferencesdisable']) && !in_array('marketing', str_replace("\r", "", explode(",", $this->request->cookie['mpcookie_preferencesdisable'])))) && (isset($this->request->cookie['cookieconsent_status']) && 'allow' === $this->request->cookie['cookieconsent_status'])) {
			$marketing = true;
		}

		if (!isset($this->request->cookie['mpcookie_preferencesdisable']) && !isset($this->request->cookie['cookieconsent_status'])) {

			if ($this->config->get('mpgdpr_cbinitial') === 'cookiemarketing_block' || $this->config->get('mpgdpr_cbinitial') === 'cookieanalyticmarketing_block') {
				$marketing = true;
			}
		}

		// this.config.get('mpgdpr_cbinitial')
		// cookieanalytic_block
		// cookiemarketing_block
		// cookieanalyticmarketing_block
		// idel
		// echo "marketing : " . ($marketing ? "allow" : "deny");

		return $marketing;
	}

	public function getAddThisCookieDenyScript() {
		/*
		 * https://stackoverflow.com/questions/20218458/how-do-i-prevent-addthis-from-using-cookies-on-my-site
		 * https://www.webtoffee.com/how-to-automatically-block-cookies-using-the-gdpr-cookie-consent-plugin/
		 * Cookie checker
		 * https://www.cookieserve.com/
		 * https://cookie-script.com/how-to-block-third-party-cookies.html
		 */
		$script = '';

		if ($this->analyticOptInOut()) {
			$script = '<script type="text/javascript">var addthis_config = {data_use_cookies_ondomain: !1, data_use_cookies: !1};</script>';
		}


		return $script;
	}

	public function getGACookieDenyScript() {
		/*
		 * https://developers.google.com/analytics/devguides/collection/gtagjs/cookie-usage
		 * https://www.optimizesmart.com/google-analytics-cookies-ultimate-guide/
		 * https://developers.google.com/analytics/devguides/collection/gajs/#disable
		 * https://stackoverflow.com/questions/10668292/is-there-a-setting-on-google-analytics-to-suppress-use-of-cookies-for-users-who
		 * This window property must be set before the tracking code is called. This property must be set on each page where you want to disable Google Analytics tracking. If the property is not set or set to false then the tracking will work as usual.
		 * If the property is not set or set to false then the tracking will work as usual.
		 *
		 */

		$script = "<script>gtag('consent', 'default', { 'ad_storage': '".($this->marketingOptInOut() ? 'granted' : 'denied')."', 'analytics_storage': '".($this->analyticOptInOut() ? 'granted' : 'denied')."'});</script>";

		// if ($this->analyticOptInOut()) {
			// $properyids = ['XXXXXX-Y'];

			// $var_window = '';


			// foreach ($properyids as $properyids) {

			// $var_window .= 'window[\'ga-disable-UA-'.$properyids.'\'] = true;';
			// }

		// https://developers.google.com/tag-platform/devguides/consent
		// https://developers.google.com/tag-platform/devguides/consent#gtag.js_4

		// <!-- Global site tag (gtag.js) - Google Analytics -->
		// <script async src="https://www.googletagmanager.com/gtag/js?id=UA-124890193-1"></script>
		// <script>
		//   window.dataLayer = window.dataLayer || [];
		//   function gtag(){dataLayer.push(arguments);}
		//   gtag('js', new Date());

		//   gtag('config', 'UA-124890193-1');
		// </script>


		// gtag('consent', 'default', {
		//   'ad_storage': $this->marketingOptInOut() ? 'denied' : 'granted',
		//   'analytics_storage': $this->analyticOptInOut() ? 'denied' : 'granted'
		// });

		// gtag('consent', 'update', {
		//   'ad_storage': 'granted'
		// });


		// gtag('consent', 'default', {
		// 'ad_storage': 'denied',
		// 'wait_for_update': 500
		// })

			// $script = '<script type="text/javascript">'.$var_window.'</script>';
		// }

		return $script;
	}

	public function acceptPolicyContactUs() {
		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_contactus') && $this->config->get('mpgdpr_policy_contactus')) {
			$this->load->language($this->extension_path . 'mpgdpr/gdpr');
			$this->load->model('catalog/information');
			$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
			$information_info = $this->model_catalog_information->getInformation($this->config->get('mpgdpr_policy_contactus'));
			if ($information_info) {
				$email = '';
				if (isset($this->request->post['email']) && !$this->customer->getId()) {
					$email = $this->request->post['email'];
				}

				$insert_data = [
					'customer_id' => $this->customer->getId(),
					'email' => $email,
					'policy_id' => $information_info['information_id'],
					// 01-05-2022: updation start
					'policy_title' => $this->config->get('mpgdpr_policy_data') ? $information_info['title'] : '',
					'policy_description' => $this->config->get('mpgdpr_policy_data') ? $information_info['description'] : '',
					// 01-05-2022: updation end
				];

				/*13 sep 2019 gdpr session starts*/
				// 01-05-2022: updation start
				$mpgdpr_policyacceptance_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addPolicyAcceptance(\Mpgdpr\Mpgdpr :: CODEPOLICYACCEPTCONTACTUS, $insert_data);
				// 01-05-2022: updation end
				// Add to request log
				$request_data = [
					'customer_id' => $this->customer->getId(),
					'email' => $email,
					'date' => date('Y-m-d H:i:s'),
					'custom_string' => sprintf($this->language->get('text_gdpr_policyacceptcontactus_custom_msg'), $mpgdpr_policyacceptance_id ),
				];
				// 01-05-2022: updation start
				$mpgdpr_requestlist_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEPOLICYACCEPTCONTACTUS, $request_data);
				// 01-05-2022: updation end
				/*13 sep 2019 gdpr session ends*/
			}
		}
	}

	/*
	 * arg.customer_id (int)
	 */
	public function acceptPolicyCustomer($arg) {
		// 01-05-2022: updation start
		$customer_id = 0;
		if (isset($arg['customer_id'])) {
			$customer_id = $arg['customer_id'];
		}
		// 01-05-2022: updation end
		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_customer')) {

			if (!$this->config->get('mpgdpr_policy_customer') && $this->config->get('config_account_id')) {
				$this->config->set('mpgdpr_policy_customer', $this->config->get('config_account_id'));
			}
			if ($this->config->get('mpgdpr_policy_customer')) {
				$this->load->language($this->extension_path . 'mpgdpr/gdpr');
				$this->load->model('catalog/information');
				// 01-05-2022: updation start
				$this->load->model('account/customer');
				// 01-05-2022: updation end
				$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
				$information_info = $this->model_catalog_information->getInformation($this->config->get('mpgdpr_policy_customer'));
				// 01-05-2022: updation start
				$customer_info = $this->model_account_customer->getCustomer($customer_id);
				// 01-05-2022: updation end
				if ($information_info) {
					$insert_data = [
						'customer_id' => $customer_id,
						'policy_id' => $information_info['information_id'],
						// 01-05-2022: updation start
						'policy_title' => $this->config->get('mpgdpr_policy_data') ? $information_info['title'] : '',
						'policy_description' => $this->config->get('mpgdpr_policy_data') ? $information_info['description'] : '',
						// 01-05-2022: updation end
					];
					/*13 sep 2019 gdpr session starts*/
					// 01-05-2022: updation start
					$mpgdpr_policyacceptance_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addPolicyAcceptance(\Mpgdpr\Mpgdpr :: CODEPOLICYACCEPTREGISTER, $insert_data);
					// 01-05-2022: updation end
					// Add to request log
					$request_data = [
						'customer_id' => $customer_id,
						// 01-05-2022: updation start
						'email' => $customer_info['email'] ? $customer_info['email'] : '',
						// 01-05-2022: updation end
						'date' => date('Y-m-d H:i:s'),
						'custom_string' => sprintf($this->language->get('text_gdpr_policyacceptregister_custom_msg'), $mpgdpr_policyacceptance_id ),
					];
					// 01-05-2022: updation start
					$mpgdpr_requestlist_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEPOLICYACCEPTREGISTER, $request_data);
					// 01-05-2022: updation end
					/*13 sep 2019 gdpr session ends*/
				}
			}
		}
	}

	/*
	 * arg.order_id (int)
	 * arg.order_info (array)
	 */
	public function acceptPolicyCheckout($arg) {
		// 01-05-2022: updation start
		$order_info = [];
		if (isset($arg['order_info'])) {
			$order_info = $arg['order_info'];
		}
		if (empty($order_info) && isset($arg['order_id'])) {
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($arg['order_id']);
		}
		if (isset($this->session->data['mpgdpr_agree']) && $this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_checkout') && !empty($order_info)) {
			// 01-05-2022: updation end
			// unset agree to checkout term & conditions
			unset($this->session->data['mpgdpr_agree']);
			if (!$this->config->get('mpgdpr_policy_checkout') && $this->config->get('config_checkout_id')) {
				$this->config->set('mpgdpr_policy_checkout', $this->config->get('config_checkout_id'));
			}
			if ($this->config->get('mpgdpr_policy_checkout')) {
				$this->load->language($this->extension_path . 'mpgdpr/gdpr');
				$this->load->model('catalog/information');
				$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
				$information_info = $this->model_catalog_information->getInformation($this->config->get('mpgdpr_policy_checkout'));

				if ($information_info) {
					$insert_data = [
						'customer_id' => $order_info['customer_id'],
						'email' => $order_info['email'],
						'policy_id' => $information_info['information_id'],
						// 01-05-2022: updation start
						'policy_title' => $this->config->get('mpgdpr_policy_data') ? $information_info['title'] : '',
						'policy_description' => $this->config->get('mpgdpr_policy_data') ? $information_info['description'] : '',
						// 01-05-2022: updation end
					];

					/*13 sep 2019 gdpr session starts*/
					// 01-05-2022: updation start
					$mpgdpr_policyacceptance_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addPolicyAcceptance(\Mpgdpr\Mpgdpr :: CODEPOLICYACCEPTCHECKOUT, $insert_data);
					// 01-05-2022: updation end
					// Add to request log
					$request_data = [
						'customer_id' => $order_info['customer_id'],
						'email' => $order_info['email'],
						'date' => date('Y-m-d H:i:s'),
						'custom_string' => sprintf($this->language->get('text_gdpr_policyacceptcheckout_custom_msg'), $mpgdpr_policyacceptance_id ),
					];
					// 01-05-2022: updation start
					$mpgdpr_requestlist_id = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEPOLICYACCEPTCHECKOUT, $request_data);
					// 01-05-2022: updation end
					/*13 sep 2019 gdpr session ends*/
				}
			}
		}
	}
	// 01-05-2022: updation end

}