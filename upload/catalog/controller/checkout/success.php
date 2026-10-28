<?php

use Agmedia\LuceedOpencartWrapper\Models\LOC_Customer;
use Agmedia\LuceedOpencartWrapper\Models\LOC_Order;
use Agmedia\LuceedOpencartWrapper\Helpers\PaymentReference;

class ControllerCheckoutSuccess extends Controller {
	public function index() {
		$this->load->language('checkout/success');

        $data['is_pickup_reservation'] = false;
        $data['payment_reference'] = '';


		if (isset($this->session->data['order_id'])) {
            $this->load->model('checkout/order');
            $order_id = (int) $this->session->data['order_id'];
            $order_info = $this->model_checkout_order->getOrder($order_id);


            if (!$order_info || empty($order_info['order_id'])) {
                $this->log->write('[Luceed] OC3 success hit but order missing. session order_id=' . $order_id);
            } else {

                    $data['paymethod'] = $order_info['payment_code'];
                    $data['shmethod'] = $order_info['shipping_code'];
                    $data['order_id'] = $order_id;
                    $data['is_pickup_reservation'] = ($data['paymethod'] === 'cod_preuzimanje' && strpos($data['shmethod'], 'pickup.pickup_') === 0);

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
                    unset($this->session->data['creditcardname']);
                    unset($this->session->data['paymentplan']);

                    if (!empty($this->session->data['boxnow'])) { unset($this->session->data['boxnow']); }

                    /*******************************************************************************
                     *                                Copyright : AGmedia                           *
                     *                              email: filip@agmedia.hr                         *
                     *******************************************************************************/
                    $nhs_no = $order_id . date("ym");
                    $poziv_nb = PaymentReference::forOrder($order_info) ?: ($nhs_no . $this->mod11INI($nhs_no));
                    $data['payment_reference'] = $poziv_nb;

                    $this->load->model('checkout/order');
                    $oc_order = $order_info;
                    $oc_order['poziv_na_broj'] = $poziv_nb;
                    try {
                        $order    = new LOC_Order($oc_order);
                        $customer = new LOC_Customer($order->getCustomerData());

                        if ( ! $customer->exist()) {
                            $customer->store();
                        }

                        $sent = $order->setCustomerUid($customer->getUid())->store();

                        if ( ! $sent) {
                            $order->recordError();
                            $this->log->write('[Luceed] Order sync failed after checkout success. order_id=' . $order_id);
                        }
                    } catch (\Throwable $e) {
                        $this->log->write('[Luceed] Checkout success sync exception. order_id=' . $order_id . ' message=' . $e->getMessage());
                    }

            }

            /*******************************************************************************
             *                              END Copyright : AGmedia                         *
             *******************************************************************************/
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
        if (!empty($order_info) && !empty($order_info['order_id'])) {
            $this->load->language('account/order');
            $this->load->model('account/order');
        $data['order_id'] = (int)$order_id;
        $data['date_added'] = date($this->language->get('date_format_short'), strtotime($order_info['date_added']));

            $data['oib'] = isset($order_info['custom_field'][1]) ? $order_info['custom_field'][1] : null;
            $data['tvrtka'] = isset($order_info['custom_field'][2]) ? $order_info['custom_field'][2] : null;

        if ($order_info['payment_address_format']) {
            $format = $order_info['payment_address_format'];
        } else {

            if($data['oib']){
                $format =  '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}'. "\n" ."Kontakt osoba: " .'{firstname} {lastname}' ;
            }
            else{
                $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
            }

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
            if($data['oib']){
                $format =  '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}'. "\n" ."Kontakt osoba: " .'{firstname} {lastname}' ;
            }
            else{
                $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
            }
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


            if ($this->customer->isLogged()) {
                $data['groupId'] = $this->customer->getGroupId();

            } else {
                $data['groupId'] ='0';
            }



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

            $product_image = $this->model_account_order->getProductImage($product['product_id']);

            $this->load->model('tool/image');

            if ($product_image) {
                $product_image = $this->model_tool_image->resize($product_image, '120', '120');
            }

            $data['products'][] = array(
                'name'     => $product['name'],
                'image' => $product_image,
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


            if($this->session->data['currency']=='HRK'){
                $text =  $this->currency->format($total['value'], $this->session->data['currency']).' <small>('.$this->currency->format($total['value'], 'EUR'). ')</small> ';
            }
            else{
                $text =  $this->currency->format($total['value'], $this->session->data['currency']);
            }

            if ($total['title']=='Ukupno'){

                $ukupno = $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value']);
               // $ukupnohub = number_format((float)$total['value'], 2, '.', '');

               // $ukupnohub = number_format($total['value'] * 100, 2, '.', '');
              //  $ukupnohub = str_replace('.', '', $ukupnohub);

                $ukupnohub = number_format((float)$total['value'], 4, '.', '');
                $ukupnohub = $ukupnohub * 100;


            }
            $data['totals'][] = array(
                'title' => $total['title'],
                'text'  => $text,
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

                  if( $data['shmethod'] == 'weight.weight_5') {
                      $data['text_message'] = sprintf($this->language->get('text_pouzeceboxnow'), $order_id);

                  } else {
                      $data['text_message'] = sprintf($this->language->get('text_pouzece'), $order_id);
                  }



              }

              elseif ($data['paymethod'] == 'cod_preuzimanje') {

                  $data['text_message'] = sprintf('<p>Uredno smo zaprimili Vašu rezervaciju broj %s</p><p>Naš tim u webshopu će je obraditi u najbržem mogućem roku.</p>', $order_id);

              }


              elseif ($data['paymethod'] == 'bank_transfer') {

                  $pozivnabroj = $data['payment_reference'] ?: PaymentReference::forOrder($order_info);

                  $data['text_message'] = sprintf($this->language->get('text_bank'), $order_id, $ukupno, $pozivnabroj);

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
                              'amount' => (int)(string)$ukupnohub,
                              'currency' => 'EUR',
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
                                      'model' => PaymentReference::HUB3_MODEL,
                                      'reference' => $pozivnabroj,
                                  ),
                              'purpose' => 'CMDT',
                              'description' => 'Web narudžba Chipoteka',
                          ),
                  );

                  $postString = json_encode($hubstring);

                  $url = 'https://hub3.bigfish.software/api/v2/barcode';
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
                      $this->db->query("UPDATE " . DB_PREFIX . "order SET scanimage = '" . $json->errors[0] . "' WHERE order_id = '" . (int)$order_id . "'");
                      $data['uplatnica'] = 'error';
                 }
                 else{

                     $response = base64_encode($response);
                     $data['uplatnica'] = $response;
                     $this->db->query("UPDATE " . DB_PREFIX . "order SET scanimage = '" . $response . "' WHERE order_id = '" . (int)$order_id . "'");


                     $scimg = 'data:image/png;base64,'.$response;

                     list($type, $scimg) = explode(';', $scimg);
                     list(, $scimg)      = explode(',', $scimg);
                     $scimg = base64_decode($scimg);

                     file_put_contents(DIR_IMAGE.'tmp/'.$order_id.'.png', $scimg);

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


    public function mod11($baseVal = "", $separator = '')
    {
        $result = "";
        $weight = [ 2, 3, 4, 5, 6, 7,
            2, 3, 4, 5, 6, 7,
            2, 3, 4, 5, 6, 7,
            2, 3, 4, 5, 6, 7 ];

        /* For convenience, reverse the string and work left to right. */
        $reversedBseVal = strrev($baseVal);
        for ($i=0, $sum=0; $i < strlen($reversedBseVal); $i++) {
            /* Calculate product and accumulate. */
            $sum += substr($reversedBseVal, $i, 1) * $weight[$i];
        }

        /* Determine check digit, and concatenate to base value. */
        $remainder = $sum % 11;
        switch ($remainder) {
            case 0:
            case 1:
                $result = "{$baseVal}{$separator}0";
                break;

            default:
                $checkDigit = 11 - $remainder;
                $result = "{$baseVal}{$separator}{$checkDigit}";
                break;
        }

        return $result;
    }


    public function mod11INI(string $nb)
    {
        $i = 0;
        $v = 0;
        $p = 2;
        $c = ' ';

        for ($i = strlen($nb); $i >= 1 ; $i--) {
            $c = substr($nb, $i - 1, 1);

            if ('0' <= $c && $c <= '9' && $v >= 0) {
                $v = $v + $p * $c;
                $p = $p + 1;
            } else {
                $v = -1;
            }
        }

        if ($v >= 0) {
            $v = 11 - ($v%11);

            if ($v > 9) {
                $v = 0;
            }
        }

        return $v;
    }
}
