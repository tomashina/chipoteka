<?php
class ControllerExtensionMpGdprEvent extends \Mpgdpr\Controller {
	use \Mpgdpr\trait_mpgdpr_catalog;

	public function __construct($registry) {
		parent :: __construct($registry);
		$this->igniteTraitMpgdprCatalog($registry);
	}

	/*
	 * heredoc disclaimer
	 * The reason why find, replace variables are stick to start tab is, in some php version lower than 7.4 heredoc syntax cause errors if there are tabs or spaces used.
	 * The reason why I use heredoc syntax is, it is convienient to use it without worry about single, double quotes in string and this is the only reason for using heredoc syntax
	 */


	private function mvqcheck($file) {
		if (class_exists('VQMod')) {
			return \VQMod::modCheck($file);
		}

		return $file;
	}

	private function getActiveTheme() {
		// $theme = $this->config->get('template_directory');

		// If the default theme is selected we need to know which directory its pointing to
		if ($this->config->get('config_theme') == 'default') {
			$theme = $this->config->get('theme_default_directory');
		} else {
			$theme = $this->config->get('config_theme');
		}

		// defined('JOURNAL3_ACTIVE')
		return $theme;
	}

	// trigger: catalog/view/*/before
	public function evViewBefore(&$route, &$data, &$code) {

		if(!$this->config->get('mpgdpr_status')) {
			return;
		}

		// echo "route : {$route}";
		// echo "\n<br/>\n";

		$filename = $this->config->get('template_directory') . $route;
		// add view routes use in extension. do if necessary
		$routes = [
			'common/header',
			'account/account',
			'information/contact',
			$this->extension_path . 'module/account',
		];

		$template_engine = '.tpl';

		if (VERSION >= '3.0.0.0') {
			$template_engine = '.twig';
			if ($this->config->get('template_engine')) {
				$template_engine = '.' . $this->config->get('template_engine');
			}
		}

		$theme_override = 'true';
		if (!$code && in_array($route, $routes)) {
			$theme_override = 'false';

			$file = DIR_TEMPLATE . $filename . $template_engine;

			if (is_file(DIR_MODIFICATION . 'catalog/view/theme/' . $filename . $template_engine)) {
				$code = file_get_contents($this->mvqcheck(DIR_MODIFICATION . 'catalog/view/theme/' . $filename . $template_engine));
			} elseif (is_file($file)) {
				$code = file_get_contents($this->mvqcheck($file));
			}

		}
		// $this->log->write("theme_override: {$theme_override}, evViewBefore(route: {$route}, data: {data}, code: {$code} )");
	}

	// <!-- add gdpr link to account page start -->
	// 'trigger' => 'catalog/view/account/account/after',
	public function accountAccount(&$route, &$data, &$output) {

		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/


		if ($this->config->get('mpgdpr_status')) {

			$this->load->language($this->extension_path . 'mpgdpr/gdpr');
			$text_my_gdpr = $this->language->get('text_my_gdpr');
			$text_tool_gdpr = $this->language->get('text_tool_gdpr');
			$tool_gdpr = $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr', '', true);

/*See 'heredoc disclaimer' to learn why below code is like this*/
$find = "<h2>{$data['text_my_newsletter']}</h2>";
$replace = <<<replace
<h2>{$text_my_gdpr}</h2>
<ul class="list-unstyled">
	<li><a href="{$tool_gdpr}">{$text_tool_gdpr}</a></li>
</ul>
replace;

			if ($this->getActiveTheme() == 'journal3') {

/*See 'heredoc disclaimer' to learn why below code is like this*/
$find = '<div class="my-newsletter">';

$replace = <<<replace
<div class="mpgdr">
	<h2 class="title">{$text_my_gdpr}</h2>
	<ul class="list-unstyled account-list">
		<li><a href="{$tool_gdpr}">{$text_tool_gdpr}</a></li>
	</ul>
</div>
<style>
.mpgdr .account-list > li > a::before {
    content: '\\eaf1' !important;
    font-family: icomoon !important;
}
</style>
replace;
			}

			if ($this->getActiveTheme() == 'journal2') {
/*See 'heredoc disclaimer' to learn why below code is like this*/
$find = '<h2 class="secondary-title">{$text_my_newsletter}</h2>';
$replace = <<<replace
<div class="mpgdr">
	<h2 class="secondary-title title">{$text_my_gdpr}</h2>
	<ul class="list-unstyled account-list">
		<li><a href="{$tool_gdpr}">{$text_tool_gdpr}</a></li>
	</ul>
</div>
<style>
.mpgdr .account-list > li > a::before {
    content: '\\eaf1' !important;
    font-family: icomoon !important;
}
</style>
replace;

			}


			$output = str_replace($find, $replace . "\n" . $find, $output);

		}

	}
	// <!-- add gdpr link to account page end -->

	// <!-- add gdpr link to account sidebar start -->
	// <!-- for oc2.3x+ versions starts -->
	// 'trigger' => 'catalog/view/extension/module/account/after',
	// 'trigger' => 'catalog/view/module/account/after',
	public function moduleAccount(&$route, &$data, &$output) {


		if ($this->config->get('mpgdpr_status')) {
			$this->load->language($this->extension_path . 'mpgdpr/gdpr');

			/*See 'heredoc disclaimer' to learn why below code is like this*/
$find = <<<find
<a href="{$data['account']}" class="list-group-item">{$data['text_account']}</a>
find;

$replace = <<<replace
<a href="{$this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr', '', true)}" class="list-group-item">{$this->language->get('text_tool_gdpr')}</a>
replace;

			$output = str_replace($find, $find . "\n" . $replace, $output);

		}

	}
	// <!-- for oc2.3x+ versions ends -->
	// <!-- add gdpr link to account sidebar end -->

	// <!-- policy acceptance at contact us page start -->
	// 'trigger' => 'catalog/view/information/contact/after',
	public function informationContactAfter(&$route, &$data, &$output) {

		/*See 'heredoc disclaimer' to learn why below code is like this*/
		if ($data['error_warning']) {

$find = <<<find
<div id="content"
find;

$replace = <<<replace
<div class="col-sm-12">
	<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> {$data['error_warning']}</div>
</div>
replace;

			$output = str_replace($find, $replace . "\n" . $find, $output);
		}


		/*See 'heredoc disclaimer' to learn why below code is like this*/
		if ($this->config->get('mpgdpr_status') && $data['text_mpgdpr_agree']) {

$find = <<<find
<input class="btn btn-primary" type="submit" value="{$data['button_submit']}" />
find;

$chk = '';
if ($data['mpgdpr_agree']) {
	$chk = 'checked="checked"';
}
$replace = <<<replace
	{$data['text_mpgdpr_agree']} <input type="checkbox" name="mpgdpr_agree" value="1" {$chk} />
replace;

			if ($this->getActiveTheme() == 'journal3') {
$find = <<<find
<button class="btn btn-primary" type="submit"><span>{$data['button_submit']}</span></button>
find;
			}

			if ($this->getActiveTheme() == 'journal2') {
$find = <<<find
<input class="btn btn-primary button" type="submit" value="{$data['button_submit']}"/>
find;
			}

			$output = str_replace($find, $replace . '&nbsp;' . $find, $output);
		}
	}

	// 'trigger' => 'catalog/view/information/contact/before',
	public function informationContact(&$route, &$data, &$code) {

		$data['mpgdpr_status'] = $this->config->get('mpgdpr_status');
		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_contactus') && $this->config->get('mpgdpr_policy_contactus')) {

			$this->load->language($this->extension_path . 'mpgdpr/gdpr');

			$this->load->model('catalog/information');
			$information_info = $this->model_catalog_information->getInformation($this->config->get('mpgdpr_policy_contactus'));
			if ($information_info) {
				$data['text_mpgdpr_agree'] = sprintf($this->language->get('text_mpgdpr_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('mpgdpr_policy_contactus'), true), $information_info['title'], $information_info['title']);
			} else {
				$data['text_mpgdpr_agree'] = '';
			}
		} else {
			$data['text_mpgdpr_agree'] = '';
		}

		if (isset($this->request->post['mpgdpr_agree'])) {
			$data['mpgdpr_agree'] = $this->request->post['mpgdpr_agree'];
		} else {
			$data['mpgdpr_agree'] = false;
		}

	}
	// <!-- policy acceptance at contact us page end -->

	// <!-- policy acceptance at register page start -->
	// 'trigger' => 'catalog/controller/account/register/before',
	public function accountRegisterBefore(&$route, &$data) {
		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/
		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_customer')) {

			if ($this->config->get('mpgdpr_policy_customer')) {

				// hold original value for config_account_id (Opencart default setting for account register page policy)
				$this->config->set('config_account_id_oldmpgdpr', $this->config->get('config_account_id'));
				$this->config->set('config_account_id', $this->config->get('mpgdpr_policy_customer'));
			}
		}
		/*mpgdpr ends*/
		/*end gdpr 28-07-2018*/
	}

	// 'trigger' => 'catalog/controller/account/register/after',
	public function accountRegisterAfter(&$route, &$data, &$output) {
		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/
		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_customer')) {

			if ($this->config->get('mpgdpr_policy_customer') && $this->config->get('config_account_id_oldmpgdpr')) {
				// restore original value for config_account_id (Opencart default setting for account register page policy)
				$this->config->set('config_account_id', $this->config->get('config_account_id_oldmpgdpr'));
			}
		}
		/*mpgdpr ends*/
		/*end gdpr 28-07-2018*/
	}

	// <!-- policy acceptance at register page end -->


	// <!-- policy acceptance at checkout page for register start -->
	// 'trigger' => 'catalog/controller/checkout/register/before',
	public function checkoutRegisterBefore(&$route, &$data) {
		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/
		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_customer')) {

			if ($this->config->get('mpgdpr_policy_customer')) {

				// hold original value for config_account_id (Opencart default setting for checkout register page policy)
				$this->config->set('config_account_id_oldmpgdpr', $this->config->get('config_account_id'));
				$this->config->set('config_account_id', $this->config->get('mpgdpr_policy_customer'));
			}
		}
		/*mpgdpr ends*/
		/*end gdpr 28-07-2018*/
	}
	// 'trigger' => 'catalog/controller/checkout/register/after',
	public function checkoutRegisterAfter(&$route, &$data, &$output) {
		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/
		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_customer')) {

			if ($this->config->get('mpgdpr_policy_customer') && $this->config->get('config_account_id_oldmpgdpr')) {
				// restore original value for config_account_id (Opencart default setting for checkout register page policy)
				$this->config->set('config_account_id', $this->config->get('config_account_id_oldmpgdpr'));
			}
		}
		/*mpgdpr ends*/
		/*end gdpr 28-07-2018*/
	}
	// 'trigger' => 'catalog/controller/checkout/register/save/before',
	public function checkoutRegisterSaveBefore(&$route, &$data) {
		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/
		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_customer')) {

			if ($this->config->get('mpgdpr_policy_customer')) {
				// hold original value for config_account_id (Opencart default setting for checkout page policy)
				$this->config->set('config_account_id_oldmpgdpr', $this->config->get('config_account_id'));
				$this->config->set('config_account_id', $this->config->get('mpgdpr_policy_customer'));
			}
		}
		/*mpgdpr ends*/
		/*end gdpr 28-07-2018*/
	}

	// 'trigger' => 'catalog/controller/checkout/register/save/after',
	public function checkoutRegisterSaveAfter(&$route, &$data, &$output) {

		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/
		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_customer')) {

			if ($this->config->get('mpgdpr_policy_customer') && $this->config->get('config_account_id_oldmpgdpr')) {
				// restore original value for config_account_id (Opencart default setting for checkout register page policy)
				$this->config->set('config_account_id', $this->config->get('config_account_id_oldmpgdpr'));
			}
		}
		/*mpgdpr ends*/
		/*end gdpr 28-07-2018*/

	}
	// <!-- policy acceptance at checkout page for register end -->

	// <!-- policy acceptance save database for register start -->
	// 'trigger' => 'catalog/model/account/customer/addCustomer/after',
	public function modelAddCustomer(&$route, &$args, &$output) {
		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/
		/* // 01-05-2022: updation start */
		$this->load->controller($this->extension_path . 'mpgdpr/acceptPolicyCustomer', array('customer_id' => &$output));
		/* // 01-05-2022: updation end */
		/*mpgdpr ends*/
		/*end gdpr 28-07-2018*/
	}
	// <!-- policy acceptance save database for register end -->

	// <!-- policy acceptance at checkout page for checkout start -->
	// 'trigger' => 'catalog/controller/checkout/payment_method/before',
	public function checkoutPaymentMethodBefore(&$route, &$data) {
		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/
		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_checkout')) {

			if ($this->config->get('mpgdpr_policy_checkout')) {
				// hold original value for config_checkout_id (Opencart default setting for checkout page policy)
				$this->config->set('config_checkout_id_oldmpgdpr', $this->config->get('config_checkout_id'));
				$this->config->set('config_checkout_id', $this->config->get('mpgdpr_policy_checkout'));
			}
		}
		/*mpgdpr ends*/
		/*end gdpr 28-07-2018*/
	}

	// 'trigger' => 'catalog/controller/checkout/payment_method/after',
	public function checkoutPaymentMethodAfter(&$route, &$data, &$output) {
		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/
		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_checkout')) {

			if ($this->config->get('mpgdpr_policy_checkout') && $this->config->get('config_checkout_id_oldmpgdpr')) {
				// restore original value for config_checkout_id (Opencart default setting for checkout page policy)
				$this->config->set('config_checkout_id', $this->config->get('config_checkout_id_oldmpgdpr'));
			}
		}
		/*mpgdpr ends*/
		/*end gdpr 28-07-2018*/
	}

	// 'trigger' => 'catalog/controller/checkout/payment_method/save/before',
	public function checkoutPaymentMethodSaveBefore(&$route, &$data) {
		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/
		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_checkout')) {

			if ($this->config->get('mpgdpr_policy_checkout')) {
				// hold original value for config_checkout_id (Opencart default setting for checkout page policy)
				$this->config->set('config_checkout_id_oldmpgdpr', $this->config->get('config_checkout_id'));
				$this->config->set('config_checkout_id', $this->config->get('mpgdpr_policy_checkout'));
			}
		}
		/*mpgdpr ends*/
		/*end gdpr 28-07-2018*/
	}

	// 'trigger' => 'catalog/controller/checkout/payment_method/save/after',
	public function checkoutPaymentMethodSaveAfter(&$route, &$data, &$output) {
		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/
		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_acceptpolicy_checkout')) {

			// save function of payment method is ajax callable and return json_encoded response, thus grabbing the response using this.response.getOutput

			$decoded_output = json_decode($this->response->getOutput(), 1);
			$error_agree = '';

			// if there is any error warning then check it for checkout policy or not.
			if (isset($decoded_output['error']) && isset($decoded_output['error']['warning'])) {

				// re-run validation for checkout policy page in events, because it's sucked.. in regard of ocmod which allows to inject piece of code directly
				if ($this->config->get('config_checkout_id')) {
					$this->load->model('catalog/information');

					$information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));

					if ($information_info && !isset($this->request->post['agree'])) {

						$error_agree = sprintf($this->language->get('error_agree'), $information_info['title']);

						// Always unset session for mpgdpr_agree, if any in case of error of checkout policy
						if (isset($this->session->data['mpgdpr_agree'])) {
							unset($this->session->data['mpgdpr_agree']);
						}
					}
				}
			}

			// user checked the checkbox and no error for checkout policy.
			if (isset($this->request->post['agree']) && empty($error_agree)) {
				$this->session->data['mpgdpr_agree'] = 1;
			}



			if ($this->config->get('mpgdpr_policy_checkout') && $this->config->get('config_checkout_id_oldmpgdpr')) {
				// restore original value for config_checkout_id (Opencart default setting for checkout page policy)
				$this->config->set('config_checkout_id', $this->config->get('config_checkout_id_oldmpgdpr'));
			}
		}
		/*mpgdpr ends*/
		/*end gdpr 28-07-2018*/
	}

	// <!-- policy acceptance at checkout page for checkout end -->

	// <!-- policy acceptance save database for checkout start -->
	// 'trigger' => 'catalog/model/checkout/order/addOrderHistory/after',
	public function modelAddOrderHistory(&$route, &$args, &$output) {

		$order_id = $args[0];
		$order_info = [];
		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/
		/* // 01-05-2022: updation start */
		$this->load->controller($this->extension_path . 'mpgdpr/mpgdpr/acceptPolicyCheckout', array('order_info' => $order_info, 'order_id' => $order_id) );
		/* // 01-05-2022: updation end */
		/*mpgdpr ends*/
		/*end gdpr 28-07-2018*/
	}
	// <!-- policy acceptance save database for checkout end -->

	// 'trigger' => 'catalog/view/common/header/before',
	public function commonHeaderBefore(&$route, &$data, &$code) {
		if (!$this->config->get('mpgdpr_status')) {
			return;
		}

		// or we can use controller common/header before/after to
		// enable/disable status of google analytic forcefully.

		// defining analytics is good way, other than use after event for model setting extensions getExtensions OR
		// view extension analytic google

		// Analytics
		$this->load->model($this->model_file[$this->extension_path . 'extension']['path']);

		$data['analytics'] = array();

		$analytics = $this->{$this->model_file[$this->extension_path . 'extension']['obj']}->getExtensions('analytics');

		foreach ($analytics as $analytic) {
			if ($this->config->get('mpgdpr_default_google_analytic') == '0' && in_array($analytic['code'], ['google','google_analytics'])) {
				continue;
			}
			if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
				$data['analytics'][] = $this->load->controller($this->extension_path . 'analytics/' . $analytic['code'], $this->config->get('analytics_' . $analytic['code'] . '_status'));
			}
		}
	}


	// 'trigger' => 'catalog/view/common/header/after',
	public function commonHeader(&$route, &$data, &$output) {

		if ($this->config->get('mpgdpr_status') && $this->config->get('mpgdpr_cbstatus')) {

			$mpgdpr_custom_js_code = html_entity_decode($this->config->get('mpgdpr_custom_js_code'), ENT_QUOTES, 'UTF-8');

			$find = '</head>';
$replace = <<<replace
<link href="catalog/view/javascript/mpgdpr/cookieconsent/cookieconsent.min.css" rel="stylesheet">
<script type="text/javascript" src="catalog/view/javascript/mpgdpr/cookieconsent/cookieconsent.js"></script>
<!-- /*start gdpr 28-07-2018*/ -->
<!-- /*mpgdpr starts*/ -->
{$mpgdpr_custom_js_code}
<script type="text/javascript">
	$(document).ready(function() {
		// remove cookie using js
		/*cookieconsent_status, mpcookie_preferencesdisable*/
		if (typeof Journal != 'undefined' && Journal.isPopup) { return; }
		$.get('index.php?route={$this->extension_path}mpgdpr/preferenceshtml/getPreferencesHtml', function(json) {
			if (json) {
				$('body').append(json);
				mpgdpr.handle_cookie();
				mpgdpr.maintainance_cookies();
				mpgdpr.cookieconsent();
				setTimeout(function() {
					//console.log(mpgdpr.instance)
				},500);
			}
		});
	});
</script>
<!-- /*mpgdpr ends*/ -->
<!-- /*end gdpr 28-07-2018*/ -->
replace;
		$output = str_replace($find, $replace . "\n" . $find, $output);
		}



	}

	// 'trigger' => 'catalog/controller/extension/analytics/google/after',
	// 'trigger' => 'catalog/controller/analytics/google/after',
	public function analyticsGoogle(&$route, &$data, &$output) {
		if (!$this->config->get('mpgdpr_status')) {
			return;
		}

		if ($output) {
			$output .= $this->load->controller($this->extension_path . 'mpgdpr/mpgdpr/getGACookieDenyScript');
		}
	}

	// 'trigger' => 'catalog/view/product/product/after',
	public function productProduct(&$route, &$data, &$output) {
		/*start gdpr 28-07-2018*/
		/*mpgdpr starts*/
		$mpgdpr_addthiscookiedenyscript = $this->load->controller($this->extension_path . 'mpgdpr/mpgdpr/getAddThisCookieDenyScript');
		/*mpgdpr ends*/
		/*end gdpr 28-07-2018*/

		$find = '<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-515eeaf54693130e"></script>';

		$output = str_replace($find, $mpgdpr_addthiscookiedenyscript . $find, $output);
	}
}