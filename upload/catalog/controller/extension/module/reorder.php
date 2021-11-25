<?php
class ControllerExtensionModuleReorder extends Controller {
	public function index() {
		if ($this->config->get('module_reorder_status')) {
			if (isset($this->request->get['order_id'])) {
				$this->load->model('account/order');

                $data['master'] = $this->customer->getMaster();

                if($data['master']=='1'){

                    $order_info = $this->model_account_order->getOrderMaster($this->request->get['order_id']);
                }
                else{
                    $order_info = $this->model_account_order->getOrder($this->request->get['order_id']);
                }



				if ($order_info) {
					$this->load->language('extension/module/reorder');

					$order_products = $this->model_account_order->getOrderProducts($this->request->get['order_id']);

					if ($order_products) {
						if ($this->config->get('module_reorder_clear')) {
							$this->cart->clear();
						}

						foreach ($order_products as $order_product) {
							$option_data = array();

							$order_options = $this->model_account_order->getOrderOptions($this->request->get['order_id'], $order_product['order_product_id']);

							foreach ($order_options as $order_option) {
								if ($order_option['type'] == 'select' || $order_option['type'] == 'radio') {
									$option_data[$order_option['product_option_id']] = $order_option['product_option_value_id'];
								} elseif ($order_option['type'] == 'checkbox') {
									$option_data[$order_option['product_option_id']][] = $order_option['product_option_value_id'];
								} elseif ($order_option['type'] == 'text' || $order_option['type'] == 'textarea' || $order_option['type'] == 'date' || $order_option['type'] == 'datetime' || $order_option['type'] == 'time') {
									$option_data[$order_option['product_option_id']] = $order_option['value'];
								} elseif ($order_option['type'] == 'file') {
									$option_data[$order_option['product_option_id']] = $this->encryption->encrypt($order_option['value']);
								}
							}

							$this->cart->add($order_product['product_id'], $order_product['quantity'], $option_data);
						}

						$this->session->data['success'] = sprintf($this->language->get('text_success_reorder'), $this->request->get['order_id']);
					}

					if ($this->config->get('module_reorder_redirect') == 'cart') {
						$this->response->redirect($this->url->link('checkout/cart'));
					} else {
						$this->response->redirect($this->url->link('checkout/checkout'));
					}
				}
			}
		}
	}
}