<?php
class ControllerExtensionAnalyticsGoogleAnalyticsExpert extends Controller {

public function index() {
$gae_exclude_admin = $this->config->get('analytics_google_analytics_expert_exclude_admin');
$gae_tracking = $this->config->get('analytics_google_analytics_expert_property_id');
$gae_status = $this->config->get('analytics_google_analytics_expert_status');
$gae_remarketing = $this->config->get('analytics_google_analytics_expert_remarketing');
$gae_cookie = $this->config->get('analytics_google_analytics_expert_cookie');
		
if ($gae_cookie == 1)  {		

$cookie_code = <<<GAE

<!-- Begin Cookie Consent -->
<script type="text/javascript">
	window.cookieconsent_options = {"message":"We use cookies to ensure you get the best experience on our website","dismiss":"Got it!","learnMore":"More info","link":null,"theme":"dark-bottom"};
</script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/1.0.9/cookieconsent.min.js"></script>
<!-- End Cookie Consent -->

GAE;
} else {
	$cookie_code = '';
}

$gae_code = html_entity_decode($this->config->get('analytics_google_analytics_expert_code'));

$gae_code .= <<<GAE


  ga('create', '$gae_tracking', 'auto');
GAE;

if ($gae_remarketing == 1) {
$gae_code .= <<<GAE

  ga('require', 'displayfeatures');
GAE;

} $gae_code .= <<<GAE

  ga('send', 'pageview');
</script>

GAE;

		
$full_code =  <<<GAE
$cookie_code
$gae_code
GAE;
		
if(version_compare(VERSION, '2.2.0.0', '>=')) {
	$user = new Cart\User($this->registry);
	$user_logged = $user->isLogged();
} else {
   $this->user = new User($this->registry);
   $user_logged = $this->user->isLogged();
}
		
if ($gae_status && (!$user_logged || $gae_exclude_admin != 1 && $user_logged))  {
	return html_entity_decode($full_code);
} else {
	return '';
}

}

public function ecommerce() {
	$this->load->model('checkout/order');
	$order_id = $this->session->data['gae_order_id'];

	if(version_compare(VERSION, '2.2.0.0', '>=')) {
		$user = new Cart\User($this->registry);
		$user_logged = $user->isLogged();
	} else {
    	$this->user = new User($this->registry);
    	$user_logged = $this->user->isLogged();
	}
		
	$orderDetails = $this->model_checkout_order->getOrder($order_id);
	$order_shipping_total = (isset($this->session->data['shipping_method']['cost'])) ? $this->session->data['shipping_method']['cost'] : 0;
	$gae_exclude_admin = $this->config->get('analytics_google_analytics_expert_exclude_admin');
	$gae_cookie = $this->config->get('analytics_google_analytics_expert_cookie');
	$gae_conversion_id = $this->config->get('analytics_google_analytics_expert_conversion_id');
	$gae_label = $this->config->get('analytics_google_analytics_expert_label');
	$gae_adwords = $this->config->get('analytics_google_analytics_expert_adwords');
	$gae_order_id = $orderDetails['order_id'];
	$gae_store_name = addslashes($orderDetails['store_name']);
	$gae_store_id = addslashes($orderDetails['store_id']);
	$gae_order_total = round($orderDetails['total'], 2);
	$gae_shipping_total = round($order_shipping_total, 2);

	$language = $this->config->get('config_language');
	$currency = $this->config->get('config_currency');
	$route = $this->request->get['route'];
	
	$order_product_query = $this->db->query("SELECT op.*,p.sku,(SELECT DISTINCT GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR ' > ') FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c1 ON (cp.category_id = c1.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (cp.path_id = c2.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE c1.category_id = pc.category_id) AS category FROM " . DB_PREFIX . "category_description cd INNER JOIN " . DB_PREFIX . "product_to_category pc ON pc.category_id = cd.category_id INNER JOIN " . DB_PREFIX . "order_product op ON pc.product_id = op.product_id INNER JOIN " . DB_PREFIX . "product p ON p.product_id = op.product_id LEFT JOIN " . DB_PREFIX . "order_option oo ON (oo.order_product_id = op.order_product_id) WHERE op.order_id = '" . (int)$gae_order_id . "' AND pc.product_id = op.product_id AND oo.order_id IS NULL GROUP BY op.order_product_id");
	$orderProduct = $order_product_query->rows;
	
	$mysqli_query = $this->db->query("SET SQL_BIG_SELECTS=1");
	$order_product_options_query = $this->db->query("SELECT op.*,p.sku,(SELECT DISTINCT GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR ' > ') FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c1 ON (cp.category_id = c1.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (cp.path_id = c2.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE c1.category_id = pc.category_id) AS category,oo.name as option_name, oo.value,oo.order_product_id,GROUP_CONCAT(DISTINCT oo.name, ': ', oo.value SEPARATOR ' - ') as options_data FROM " . DB_PREFIX . "category_description cd INNER JOIN " . DB_PREFIX . "product_to_category pc ON pc.category_id = cd.category_id INNER JOIN " . DB_PREFIX . "order_product op ON pc.product_id = op.product_id INNER JOIN " . DB_PREFIX . "product p ON p.product_id = op.product_id INNER JOIN " . DB_PREFIX . "order_option oo ON op.order_product_id = oo.order_product_id WHERE op.order_id = '" . (int)$gae_order_id . "' AND pc.product_id = op.product_id AND op.order_product_id = oo.order_product_id GROUP BY oo.order_product_id");
	$orderProductOptions = $order_product_options_query->rows;
	
	$tax_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$gae_order_id . "' AND code = 'tax'");
			
	if ($tax_query->num_rows) {
		$gae_order_tax = round($tax_query->row['value'], 2);
	} else {
		$gae_order_tax = '0.00';
	}
	
	if (isset($orderDetails)) {
		$gae_conversion_value = round(($gae_order_total - $gae_order_tax), 2);
	} else {
		$gae_conversion_value = '';
	}
	
	if (!$user_logged || $gae_exclude_admin != 1 && $user_logged) {    

$gae_ecommerce = <<<GAE
	<script type="text/javascript">
		ga('require', 'ecommerce', 'ecommerce.js');

		ga('ecommerce:addTransaction', {
        	'id': '$gae_order_id',
        	'affiliation': '$gae_store_name',
        	'revenue': '$gae_order_total',
        	'tax': '$gae_order_tax',
        	'shipping': '$gae_shipping_total'
		});
GAE;
		if (isset($orderProduct)) {
 			foreach ($orderProduct as $product) {
 				$gae_product_order_id = $product['order_id'];
				$gae_product_sku = !empty($product['sku']) ? json_encode(html_entity_decode($product['sku'],ENT_QUOTES, 'UTF-8')) : json_encode(html_entity_decode($product['model'],ENT_QUOTES, 'UTF-8'));
				$gae_product_name = json_encode(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'));
				$gae_product_category = $product['category'];
				$gae_product_price = $product['price'];
				$gae_product_quantity = $product['quantity'];
	
$gae_ecommerce .= <<<GAE


		ga('ecommerce:addItem', {
            	'id': '$gae_product_order_id',
            	'sku': $gae_product_sku,
            	'name': $gae_product_name,
            	'category': '$gae_product_category',
            	'price': '$gae_product_price',
            	'quantity': '$gae_product_quantity'
        	});
GAE;
			}
 		}
 		
 		if (isset($orderProductOptions)) {
			foreach ($orderProductOptions as $product) {
				$gae_product_options_order_id = $product['order_id'];
				$gae_product_options_sku = !empty($product['sku']) ? json_encode(html_entity_decode($product['sku'],ENT_QUOTES, 'UTF-8')) : json_encode(html_entity_decode($product['model'],ENT_QUOTES, 'UTF-8'));
				$gae_product_options_name = json_encode(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'));
				$gae_product_options_category = $product['category'];
				$gae_product_options_price = $product['price'];
				$gae_product_options_quantity = $product['quantity'];

$gae_ecommerce .= <<<GAE


		ga('ecommerce:addItem', {
				'id': '$gae_product_options_order_id',
				'sku': $gae_product_options_sku,
				'name': $gae_product_options_name,
				'category': '$gae_product_options_category',
				'price': '$gae_product_options_price',
				'quantity': '$gae_product_options_quantity'
			});
GAE;
			}
 		}

$gae_ecommerce .= <<<GAE

		ga('ecommerce:send');
	</script>
	
	
GAE;
         
         if ($gae_adwords == 1) {

$gae_ecommerce .= <<<GAE
            <!-- begin Google Code for Adwords Conversion Page -->
            <script type="text/javascript">
            	var google_conversion_id = $gae_conversion_id;
            	var google_conversion_language = "$language";
            	var google_conversion_format = "3";
            	var google_conversion_color = "666666";
            	var google_conversion_label = "$gae_label";
            	var google_conversion_value = $gae_conversion_value;
            	var google_conversion_currency = "$currency;";
            	var google_remarketing_only = false;
            </script>
            <script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js"></script>
            <noscript>
            	<div style="display:none;visibility:hidden">
            		<img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/$gae_conversion_id/?value=$gae_conversion_value&amp;currency_code=$currency&amp;label=$gae_label&amp;guid=ON&amp;script=0"/>
            	</div>
            </noscript>
            <!-- end Google Code for Adwords Conversion Page -->
GAE;
         }

		$data['gae_ecommerce_code'] = $gae_ecommerce;

		if (!empty($data['gae_ecommerce_code'])) {
			return $data['gae_ecommerce_code'];
		} else {
			return '';
		}
	}
}

}