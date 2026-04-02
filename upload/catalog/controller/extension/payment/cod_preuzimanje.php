<?php
class ControllerExtensionPaymentCodPreuzimanje extends Controller {
	public function index() {
		return $this->load->view('extension/payment/cod_preuzimanje');
        $data['checkout'] = $this->url->link('checkout/checkout', '', true);
	}

	public function confirm() {
		$json = array();
		
		if (isset($this->session->data['payment_method']['code']) && $this->session->data['payment_method']['code'] == 'cod_preuzimanje') {
			$this->load->model('checkout/order');

         /*   $loc = new \Agmedia\LuceedOpencartWrapper\Models\LOC_Warehouse();

            if ( ! $loc->itemHasStock($this->request->get['sku'])) {
                $json['redirect'] = $this->url->link('checkout/cart');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }*/



			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_cod_preuzimanje_order_status_id'));
		
			$json['redirect'] = $this->url->link('checkout/success');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}
}
