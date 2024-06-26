<?php
class ControllerCommonFooter extends Controller {
	public function index() {
		$this->load->language('common/footer');

        if ($this->customer->isLogged()) {
            $data['groupId'] = $this->customer->getGroupId();

        } else {
            $data['groupId'] ='0';
        }

        $mssConfig = $this->config->get( 'msmart_search_s' );
        $mssConfigLf = (array) $this->config->get( 'msmart_search_lf' );
        $mssVer = ! empty( $mssConfig['minify_support'] ) ? '' : '?v' .$this->config->get( 'msmart_search_version' );
        $mssFiles = array(
            'js' => array( 'js_params.js', 'bloodhound.min.js', 'typeahead.jquery.min.js', 'live_search.js' ),
           // 'css' => array( 'style.css', 'style-2.css' ),
        );

        foreach( $mssFiles as $mssType => $mssFiles2 ) {
            $mssPath = $mssType == 'js' ? 'catalog/view/javascript/mss/' : 'catalog/view/theme/default/stylesheet/mss/';

            foreach( $mssFiles2 as $mssFile ) {
                $this->document->{'add'.($mssType == 'js' ? 'Script' : 'Style')}( $mssPath . $mssFile . $mssVer . ( $mssVer && $mssFile == 'js_params.js' ? '_'.time() : '' ), 'footer' );
            }
        }



		$this->load->model('catalog/information');

		$data['informations'] = array();

		foreach ($this->model_catalog_information->getInformations() as $result) {
			if ($result['bottom']) {
				$data['informations'][] = array(
					'title' => $result['title'],
					'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
				);
			}
		}

		$data['contact'] = $this->url->link('information/contact');
		$data['return'] = $this->url->link('account/return/add', '', true);
		$data['sitemap'] = $this->url->link('information/sitemap');
		$data['tracking'] = $this->url->link('information/tracking');
		$data['manufacturer'] = $this->url->link('product/manufacturer');
		$data['voucher'] = $this->url->link('account/voucher', '', true);
		$data['affiliate'] = $this->url->link('affiliate/login', '', true);
		$data['special'] = $this->url->link('product/special');
		$data['account'] = $this->url->link('account/account', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['newsletter'] = $this->url->link('account/newsletter', '', true);

        $data['cart'] = $this->load->controller('common/cart');

        $data['shopping_cart'] = $this->url->link('checkout/cart');
        // Cart Items
        $data['cart_items'] = $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0);

        // Cart Total
        $data['cart_amount'] = $this->load->controller('extension/basel/basel_features/total_amount');

        $data['login'] = $this->url->link('account/login', '', true);
		$data['powered'] = sprintf($this->language->get('text_powered'), $this->config->get('config_name'), date('Y', time()));

        $data['year'] = date('Y', time());
        if (isset($this->request->get['route'])){
            $data['kategorija'] = $this->request->get['route'];
        }


		// Whos Online
		if ($this->config->get('config_customer_online')) {
			$this->load->model('tool/online');

			if (isset($this->request->server['REMOTE_ADDR'])) {
				$ip = $this->request->server['REMOTE_ADDR'];
			} else {
				$ip = '';
			}

			if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
				$url = ($this->request->server['HTTPS'] ? 'https://' : 'http://') . $this->request->server['HTTP_HOST'] . $this->request->server['REQUEST_URI'];
			} else {
				$url = '';
			}

			if (isset($this->request->server['HTTP_REFERER'])) {
				$referer = $this->request->server['HTTP_REFERER'];
			} else {
				$referer = '';
			}

			$this->model_tool_online->addOnline($ip, $this->customer->getId(), $url, $referer);
		}

		$data['scripts'] = $this->document->getScripts('footer');
		$data['styles'] = $this->document->getStyles('footer');
		
		return $this->load->view('common/footer', $data);
	}
}
