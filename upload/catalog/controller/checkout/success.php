<?php
class ControllerCheckoutSuccess extends Controller {
	public function index() {
		$this->load->language('checkout/success');

		if (isset($this->session->data['order_id'])) {
            $this->load->model('checkout/order');
            $order_id = $this->session->data['order_id'];

            $order_info = $this->model_checkout_order->getOrder($order_id);

            $data['paymethod'] = $order_info['payment_code'];
            $data['order_id'] = $order_id;

            $this->cart->clear();

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['guest']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);
			unset($this->session->data['totals']);



		}

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_basket'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_checkout'),
			'href' => $this->url->link('checkout/checkout', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_success'),
			'href' => $this->url->link('checkout/success')
		);

        ///orderinfo
        if (isset($order_id)) {
            $this->load->language('account/order');
            $this->load->model('account/order');
        $data['order_id'] = (int)$order_id;
        $data['date_added'] = date($this->language->get('date_format_short'), strtotime($order_info['date_added']));

        if ($order_info['payment_address_format']) {
            $format = $order_info['payment_address_format'];
        } else {
            $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
        }

        $find = array(
            '{firstname}',
            '{lastname}',
            '{company}',
            '{address_1}',
            '{address_2}',
            '{city}',
            '{postcode}',
            '{zone}',
            '{zone_code}',
            '{country}'
        );

        $replace = array(
            'firstname' => $order_info['payment_firstname'],
            'lastname'  => $order_info['payment_lastname'],
            'company'   => $order_info['payment_company'],
            'address_1' => $order_info['payment_address_1'],
            'address_2' => $order_info['payment_address_2'],
            'city'      => $order_info['payment_city'],
            'postcode'  => $order_info['payment_postcode'],
            'zone'      => $order_info['payment_zone'],
            'zone_code' => $order_info['payment_zone_code'],
            'country'   => $order_info['payment_country']
        );

        $data['payment_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

        $data['payment_method'] = $order_info['payment_method'];

        if ($order_info['shipping_address_format']) {
            $format = $order_info['shipping_address_format'];
        } else {
            $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
        }

        $find = array(
            '{firstname}',
            '{lastname}',
            '{company}',
            '{address_1}',
            '{address_2}',
            '{city}',
            '{postcode}',
            '{zone}',
            '{zone_code}',
            '{country}'
        );

        $replace = array(
            'firstname' => $order_info['shipping_firstname'],
            'lastname'  => $order_info['shipping_lastname'],
            'company'   => $order_info['shipping_company'],
            'address_1' => $order_info['shipping_address_1'],
            'address_2' => $order_info['shipping_address_2'],
            'city'      => $order_info['shipping_city'],
            'postcode'  => $order_info['shipping_postcode'],
            'zone'      => $order_info['shipping_zone'],
            'zone_code' => $order_info['shipping_zone_code'],
            'country'   => $order_info['shipping_country']
        );

        $data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

        $data['shipping_method'] = $order_info['shipping_method'];

        $this->load->model('catalog/product');
        $this->load->model('tool/upload');

        // Products
        $data['products'] = array();

        $products = $this->model_account_order->getOrderProducts($data['order_id']);

        foreach ($products as $product) {
            $option_data = array();

            $options = $this->model_account_order->getOrderOptions($data['order_id'] , $product['order_product_id']);

            foreach ($options as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                } else {
                    $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                    if ($upload_info) {
                        $value = $upload_info['name'];
                    } else {
                        $value = '';
                    }
                }

                $option_data[] = array(
                    'name'  => $option['name'],
                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                );
            }

            $product_info = $this->model_catalog_product->getProduct($product['product_id']);

            if ($product_info) {
                $reorder = $this->url->link('account/order/reorder', 'order_id=' . $order_id . '&order_product_id=' . $product['order_product_id'], true);
            } else {
                $reorder = '';
            }

            $data['products'][] = array(
                'name'     => $product['name'],
                'model'    => $product['model'],
                'option'   => $option_data,
                'quantity' => $product['quantity'],
                'price'    => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
                'total'    => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
                'reorder'  => $reorder,
                'return'   => $this->url->link('account/return/add', 'order_id=' . $order_info['order_id'] . '&product_id=' . $product['product_id'], true)
            );
        }

        // Voucher
        $data['vouchers'] = array();

        $vouchers = $this->model_account_order->getOrderVouchers($data['order_id'] );

        foreach ($vouchers as $voucher) {
            $data['vouchers'][] = array(
                'description' => $voucher['description'],
                'amount'      => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value'])
            );
        }

        // Totals
        $data['totals'] = array();

        $totals = $this->model_account_order->getOrderTotals($data['order_id'] );

        foreach ($totals as $total) {

            if ($total['title']=='Ukupno'){

                $ukupno = $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value']);
                $ukupnohub = number_format((float)$total['value'], 2, '.', '');
            }
            $data['totals'][] = array(
                'title' => $total['title'],
                'text'  => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value']),
            );
        }

        $data['comment'] = nl2br($order_info['comment']);

        // History
        $data['histories'] = array();

        $results = $this->model_account_order->getOrderHistories($data['order_id'] );

        foreach ($results as $result) {
            $data['histories'][] = array(
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'status'     => $result['status'],
                'comment'    => $result['notify'] ? nl2br($result['comment']) : ''
            );
        }

        }
        /// orderinoend
          if (isset($data['paymethod'])) {

              if ($data['paymethod'] == 'cod') {

                  $data['text_message'] = sprintf($this->language->get('text_pouzece'), $order_id);

              }
              else if ($data['paymethod'] == 'bank_transfer') {

                  $data['text_message'] = sprintf($this->language->get('text_bank'), $order_id, $ukupno, $order_id);

                  $hubstring = array (
                      'renderer' => 'image',
                      'options' =>
                          array (
                              "format" => "jpg",
                              "scale" =>  3,
                              "ratio" =>  3,
                              "color" =>  "#2c3e50",
                              "bgColor" => "#fff",
                              "padding" => 20
                          ),
                      'data' =>
                          array (
                              //'amount' => floatval($ukupnohub),

                              'amount' => '1000',
                              'sender' =>
                                  array (
                                      'name' => $order_info['payment_firstname'].' '.$order_info['payment_lastname'],
                                      'street' => $order_info['shipping_address_1'],
                                      'place' => $order_info['shipping_postcode'].' '.$order_info['shipping_city'],
                                  ),
                              'receiver' =>
                                  array (
                                      'name' => 'Z - EL d.o.o.',
                                      'street' => 'Industrijska cesta 28',
                                      'place' => '10360 Sesvete ',
                                      'iban' => 'HR4424070001100582698',
                                      'model' => '05',
                                      'reference' => '21416540',
                                  ),
                              'purpose' => 'CMDT',
                              'description' => 'Web narudžba Chipoteka',
                          ),
                  );

                  $postString = json_encode($hubstring);

                  $url = 'https://hub3.bigfish.software/api/v1/barcode';
                  $ch = curl_init($url);

                  # Setting our options
                  curl_setopt($ch, CURLOPT_POST, 1);
                  curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
                  curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                  curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                  # Get the response

                  $response = curl_exec($ch);
                  curl_close($ch);


                  $json = json_decode($response);


                 if(isset($json->message)){
                      $this->db->query("UPDATE " . DB_PREFIX . "order SET scanimage = '" . $response->errors[0] . "' WHERE order_id = '" . (int)$order_id . "'");
                      $data['uplatnica'] = 'error';
                 }
                 else{
                     $response = base64_encode($response);
                     $data['uplatnica'] = $response;
                     $this->db->query("UPDATE " . DB_PREFIX . "order SET scanimage = '" . $response . "' WHERE order_id = '" . (int)$order_id . "'");

                 }


              }
              else if ($data['paymethod'] == 'wspay') {

                  $data['text_message'] = sprintf($this->language->get('text_wspay'), $order_id);

              }

          }

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/success', $data));
	}
}