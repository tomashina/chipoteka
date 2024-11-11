<?php
class ControllerExtensionPaymentCod extends Controller {
	public function index() {
		return $this->load->view('extension/payment/cod');
        $data['checkout'] = $this->url->link('checkout/checkout', '', true);
	}

    public function confirm() {
        $json = array();

        if (isset($this->session->data['payment_method']['code']) && $this->session->data['payment_method']['code'] == 'cod') {
            $this->load->model('checkout/order');

            $loc = new \Agmedia\LuceedOpencartWrapper\Models\LOC_Warehouse();
            $pass = true;

            foreach ($this->cart->getProducts() as $product) {
                if ( ! $loc->itemHasStock($product['model'])) {
                    $pass = false;
                }
            }

            if ($pass) {
                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_cod_order_status_id'));

                $json['redirect'] = $this->url->link('checkout/success');
            } else {
                $json['redirect'] = $this->url->link('checkout/cart');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
