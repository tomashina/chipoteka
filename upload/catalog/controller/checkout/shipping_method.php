<?php
class ControllerCheckoutShippingMethod extends Controller {
	public function index() {
		$this->load->language('checkout/checkout');
		$pickup_enabled = (int)$this->config->get('luceed_sync_pickup_stores_enabled') === 1;
		$pickup_map = $this->getConfiguredPickupMap();
		$pickup_stores = array();

		if ($pickup_enabled) {
			$pickup_stores = $this->getPickupStoresForCheckout($pickup_map);
		}

		if (isset($this->session->data['shipping_address'])) {
			// Shipping Methods
			$method_data = array();

			$this->load->model('setting/extension');

            $data['checkout'] = $this->url->link('checkout/checkout', '', true);

			$results = $this->model_setting_extension->getExtensions('shipping');

				foreach ($results as $result) {
					// Pickup is built from import.warehouse.pickup; xshippingpro is intentionally excluded.
					if (in_array($result['code'], array('pickup', 'xshippingpro'), true)) {
						continue;
					}

				if ($this->config->get('shipping_' . $result['code'] . '_status')) {
					$this->load->model('extension/shipping/' . $result['code']);

					$quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($this->session->data['shipping_address']);

					if ($quote) {
						$method_data[$result['code']] = array(
							'title'      => $quote['title'],
							'quote'      => $quote['quote'],
							'sort_order' => $quote['sort_order'],
							'error'      => $quote['error']
						);
					}
				}
			}

			if ($pickup_enabled) {
				$pickup_method = $this->buildPickupShippingMethod($pickup_stores);

				if ($pickup_method) {
					$method_data['pickup'] = $pickup_method;
				}
			}

			$sort_order = array();

			foreach ($method_data as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $method_data);

			$this->session->data['shipping_methods'] = $method_data;

				if (!empty($this->session->data['shipping_method']['code'])) {
					$selected_code = $this->session->data['shipping_method']['code'];
					$pickup_selected = strpos($selected_code, 'pickup.pickup_') === 0;
					$xshipping_selected = strpos($selected_code, 'xshippingpro.') === 0;

					if (($pickup_selected && !$pickup_enabled) || $xshipping_selected) {
						unset($this->session->data['shipping_method']);
					}
				}
			}

		if (empty($this->session->data['shipping_methods'])) {
			$data['error_warning'] = sprintf($this->language->get('error_no_shipping'), $this->url->link('information/contact'));
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['shipping_methods'])) {
			$data['shipping_methods'] = $this->session->data['shipping_methods'];
		} else {
			$data['shipping_methods'] = array();
		}

		if (isset($this->session->data['shipping_method']['code'])) {
			$data['code'] = $this->session->data['shipping_method']['code'];
		} else {
			$data['code'] = '';
		}

		if (isset($this->session->data['comment'])) {
			$data['comment'] = $this->session->data['comment'];
		} else {
			$data['comment'] = '';
		}

		$data['pickup_stores_enabled'] = $pickup_enabled && !empty($pickup_stores);
		$data['pickup_stores'] = $pickup_stores;
		
		$this->response->setOutput($this->load->view('checkout/shipping_method', $data));
	}

	public function save() {
		$this->load->language('checkout/checkout');

		$json = array();

		// Validate if shipping is required. If not the customer should not have reached this page.
		if (!$this->cart->hasShipping()) {
			$json['redirect'] = $this->url->link('checkout/checkout', '', true);
		}

		// Validate if shipping address has been set.
		if (!isset($this->session->data['shipping_address'])) {
			$json['redirect'] = $this->url->link('checkout/checkout', '', true);
		}

		// Validate cart has products and has stock.
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout') && $this->customer->getGroupId() < '3')) {
			$json['redirect'] = $this->url->link('checkout/cart');
		}

		// Validate minimum quantity requirements.
		$products = $this->cart->getProducts();

		foreach ($products as $product) {
			$product_total = 0;

			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$json['redirect'] = $this->url->link('checkout/cart');

				break;
			}
		}

		if (!isset($this->request->post['shipping_method'])) {
			$json['error']['warning'] = $this->language->get('error_shipping');
		} else {
			$shipping = explode('.', $this->request->post['shipping_method']);

			if (!isset($shipping[0]) || !isset($shipping[1]) || !isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
				$json['error']['warning'] = $this->language->get('error_shipping');
			}
		}

		if (!$json) {
			$this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];

			$this->session->data['comment'] = strip_tags($this->request->post['comment']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	    public function saveBranchOrder() {
	        $json = array();
	        if(!empty($this->request->post['boxnow'])){
	            $this->session->data['boxnow'] = $this->request->post['boxnow'];

        }

	        $this->response->addHeader('Content-Type: application/json');
	        $this->response->setOutput(json_encode($json));

	    }

		private function buildPickupShippingMethod(array $stores) {
			if (empty($stores)) {
				return array();
			}

			$quote_data = array();

			foreach ($stores as $store) {
				$key = 'pickup_' . $store['code'];
				$title = 'Preuzimanje: ' . $store['name'];

				if (!empty($store['address'])) {
					$title .= ' (' . $store['address'] . ')';
				}

				$quote_data[$key] = array(
					'code'         => $store['shipping_code'],
					'title'        => $title,
					'cost'         => 0.00,
					'tax_class_id' => 0,
					'text'         => $this->currency->format(0.00, $this->session->data['currency'])
				);
			}

			return array(
				'code'       => 'pickup',
				'title'      => 'Preuzimanje u poslovnici',
				'quote'      => $quote_data,
				'sort_order' => (int)$this->config->get('shipping_pickup_sort_order'),
				'error'      => false
			);
		}

		private function getConfiguredPickupMap() {
			return $this->parsePickupMap(agconf('import.warehouse.pickup'));
		}

		private function parsePickupMap($map) {
			if (is_string($map) && $map !== '') {
				$parts = preg_split('/[\s,;]+/', trim($map));
				$map = is_array($parts) ? $parts : array();
			}

			if (!is_array($map) || empty($map)) {
				return array();
			}

			$normalized = array();

			foreach ($map as $key => $value) {
				$store_code = is_string($key) ? $value : $value;

				if (is_array($store_code) || $store_code === null || $store_code === '') {
					continue;
				}

				$normalized[] = $this->normalizeStoreCode($store_code);
			}

			$normalized = array_values(array_unique(array_filter($normalized, function($code) {
				return $code !== '' && ctype_digit((string)$code);
			})));

			return $normalized;
		}

		private function getPickupStoresForCheckout(array $pickup_map) {
			$hours = agconf('poslovnice_radno_vrijeme');
			$hours = is_array($hours) ? $hours : array();
			$stores = $this->loadPickupStoresIndex();
			$response = array();

			foreach ($pickup_map as $store_code) {
				$code = $this->normalizeStoreCode($store_code);

				if ($this->isPickupStoreExcludedFromCheckout($code)) {
					continue;
				}

				if (!isset($response[$code])) {
					$row = isset($stores[$code]) ? $stores[$code] : array();

					$response[$code] = array(
						'code'          => $code,
						'name'          => isset($row['naziv']) ? $row['naziv'] : ('Poslovnica ' . $code),
						'address'       => isset($row['adresa']) ? $row['adresa'] : '',
						'working_hours' => isset($hours[$code]) ? $hours[$code] : '',
						'shipping_code' => 'pickup.pickup_' . $code
					);
				}
			}

			uasort($response, function($a, $b) {
				return strcmp($a['code'], $b['code']);
			});

			return array_values($response);
		}

		private function isPickupStoreExcludedFromCheckout($store_code) {
			return in_array((string)$store_code, array('101'), true);
		}

		private function loadPickupStoresIndex() {
			$sources = array();
			$primary = agconf('import.warehouse.json');

			if (is_string($primary) && $primary) {
				$sources[] = $primary;
			}

			$sources[] = DIR_STORAGE . 'upload/assets/skladista.json';
			$sources[] = DIR_STORAGE . 'upload/luceed_json/skladista_lista.json';
			$sources = array_values(array_unique($sources));

			foreach ($sources as $path) {
				if (!is_file($path)) {
					continue;
				}

				$content = json_decode(file_get_contents($path), true);

				if (!is_array($content)) {
					continue;
				}

				$list = $this->extractStoreRows($content);

				if (empty($list)) {
					continue;
				}

				$indexed = array();

				foreach ($list as $row) {
					if (!is_array($row) || !isset($row['skladiste'])) {
						continue;
					}

					$code = $this->normalizeStoreCode($row['skladiste']);
					$row['skladiste'] = $code;
					$indexed[$code] = $row;
				}

				if (!empty($indexed)) {
					return $indexed;
				}
			}

			return array();
		}

		private function extractStoreRows(array $content) {
			if (isset($content['result'][0]['skladista']) && is_array($content['result'][0]['skladista'])) {
				return $content['result'][0]['skladista'];
			}

			return $content;
		}

		private function normalizeStoreCode($code) {
			$code = (string)$code;

			if ($code !== '' && ctype_digit($code) && strlen($code) < 3) {
				return str_pad($code, 3, '0', STR_PAD_LEFT);
			}

			return $code;
		}
	}
