<?php
class ControllerCheckoutGuestShipping extends Controller {
	public function index() {
		$this->load->language('checkout/checkout');

        $data['cart'] = $this->url->link('checkout/cart', '', true);

		if (isset($this->session->data['shipping_address']['firstname'])) {
			$data['firstname'] = $this->session->data['shipping_address']['firstname'];
		} else {
			$data['firstname'] = '';
		}

		if (isset($this->session->data['shipping_address']['lastname'])) {
			$data['lastname'] = $this->session->data['shipping_address']['lastname'];
		} else {
			$data['lastname'] = '';
		}

		if (isset($this->session->data['shipping_address']['company'])) {
			$data['company'] = $this->session->data['shipping_address']['company'];
		} else {
			$data['company'] = '';
		}

		if (isset($this->session->data['shipping_address']['address_1'])) {
			$data['address_1'] = $this->session->data['shipping_address']['address_1'];
		} else {
			$data['address_1'] = '';
		}

		if (isset($this->session->data['shipping_address']['address_2'])) {
			$data['address_2'] = $this->session->data['shipping_address']['address_2'];
		} else {
			$data['address_2'] = '';
		}

		if (isset($this->session->data['shipping_address']['postcode'])) {
			$data['postcode'] = $this->session->data['shipping_address']['postcode'];
		} else {
			$data['postcode'] = '';
		}

		if (isset($this->session->data['shipping_address']['city'])) {
			$data['city'] = $this->session->data['shipping_address']['city'];
		} else {
			$data['city'] = '';
		}

		if (isset($this->session->data['shipping_address']['country_id'])) {
			$data['country_id'] = $this->session->data['shipping_address']['country_id'];
		} else {
			$data['country_id'] = $this->config->get('config_country_id');
		}

		if (isset($this->session->data['shipping_address']['zone_id'])) {
			$data['zone_id'] = $this->session->data['shipping_address']['zone_id'];
		} else {
			$data['zone_id'] = '';
		}

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		// Custom Fields
		$this->load->model('account/custom_field');
		
		$custom_fields = $this->model_account_custom_field->getCustomFields($this->session->data['guest']['customer_group_id']);

		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'address') {
				$data['custom_fields'][] = $custom_field;
			}
		}
		
		if (isset($this->session->data['shipping_address']['custom_field'])) {
			$data['address_custom_field'] = $this->session->data['shipping_address']['custom_field'];
		} else {
			$data['address_custom_field'] = array();
		}
		
		$this->response->setOutput($this->load->view('checkout/guest_shipping', $data));
	}

	public function save() {
		$this->load->language('checkout/checkout');

		$json = array();

		// Validate if customer is logged in.
		if ($this->customer->isLogged()) {
			$json['redirect'] = $this->url->link('checkout/checkout', '', true);
		}

		// Validate cart has products and has stock.
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$json['redirect'] = $this->url->link('checkout/cart');
		}

		// Check if guest checkout is available.
		if (!$this->config->get('config_checkout_guest') || $this->config->get('config_customer_price') || $this->cart->hasDownload()) {
			$json['redirect'] = $this->url->link('checkout/checkout', '', true);
		}

		if (!$json) {
			if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) {
				$json['error']['firstname'] = $this->language->get('error_firstname');
			}

			if ((utf8_strlen(trim($this->request->post['lastname'])) < 1) || (utf8_strlen(trim($this->request->post['lastname'])) > 32)) {
				$json['error']['lastname'] = $this->language->get('error_lastname');
			}


            if(!preg_match('([a-zA-Z].*[0-9]|[0-9].*[a-zA-Z])', $this->request->post['address_1']) ){
                $json['error']['address_1'] = 'Adresa treba sadržavati i broj.';

            }

            if ((utf8_strlen(trim($this->request->post['city'])) < 2) || (utf8_strlen(trim($this->request->post['city'])) > 128)) {
				$json['error']['city'] = $this->language->get('error_city');
			}

            $loc = new \Agmedia\LuceedOpencartWrapper\Models\LOC_Places();
            $findc = $loc->getList()->find($this->request->post['city']);

            if ( ! $findc->places->count()) {
                $json['error']['city'] = 'Morate odabrati grad sa liste!';
            }

			$this->load->model('localisation/country');

			$country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);

			if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($this->request->post['postcode'])) < 5 || utf8_strlen(trim($this->request->post['postcode'])) > 5)) {
				$json['error']['postcode'] = $this->language->get('error_postcode');
			}


            $findp = $loc->getList()->find($this->request->post['postcode'], 'zipcode');

            if ( ! $findp->places->count()) {
                $json['error']['postcode'] = 'Morate odabrati poštanski broj sa liste!';
            }

            if ( ! $json) {
                $list = $loc->getList();
                $findall = $list->places->where('cityname', $this->request->post['city'])
                    ->where('zipcode', $this->request->post['postcode'])
                    ->count();

                if ( ! $findall) {
                    $json['error']['city'] = 'Grad i poštanski broj moraju odgovarati.';
                    $json['error']['postcode'] = 'Poštanski broj i grad moraju odgovarati.';
                }

            }


            if ($this->request->post['country_id'] == '') {
				$json['error']['country'] = $this->language->get('error_country');
			}

			/*if (!isset($this->request->post['zone_id']) || $this->request->post['zone_id'] == '' || !is_numeric($this->request->post['zone_id'])) {
				$json['error']['zone'] = $this->language->get('error_zone');
			}*/

			// Custom field validation
			$this->load->model('account/custom_field');

			$custom_fields = $this->model_account_custom_field->getCustomFields($this->session->data['guest']['customer_group_id']);

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'address') { 
					if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
						$json['error']['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
					} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
						$json['error']['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
					}
				}
			}
		}

		if (!$json) {
			$this->session->data['shipping_address']['firstname'] = $this->request->post['firstname'];
			$this->session->data['shipping_address']['lastname'] = $this->request->post['lastname'];
			$this->session->data['shipping_address']['company'] = $this->request->post['company'];
			$this->session->data['shipping_address']['address_1'] = $this->request->post['address_1'];
			$this->session->data['shipping_address']['address_2'] = $this->request->post['address_2'];
			$this->session->data['shipping_address']['postcode'] = $this->request->post['postcode'];
			$this->session->data['shipping_address']['city'] = $this->request->post['city'];
			$this->session->data['shipping_address']['country_id'] = $this->request->post['country_id'];
			$this->session->data['shipping_address']['zone_id'] = $this->request->post['zone_id'];

			$this->load->model('localisation/country');

			$country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);

			if ($country_info) {
				$this->session->data['shipping_address']['country'] = $country_info['name'];
				$this->session->data['shipping_address']['iso_code_2'] = $country_info['iso_code_2'];
				$this->session->data['shipping_address']['iso_code_3'] = $country_info['iso_code_3'];
				$this->session->data['shipping_address']['address_format'] = $country_info['address_format'];
			} else {
				$this->session->data['shipping_address']['country'] = '';
				$this->session->data['shipping_address']['iso_code_2'] = '';
				$this->session->data['shipping_address']['iso_code_3'] = '';
				$this->session->data['shipping_address']['address_format'] = '';
			}

			$this->load->model('localisation/zone');

			$zone_info = $this->model_localisation_zone->getZone($this->request->post['zone_id']);

			if ($zone_info) {
				$this->session->data['shipping_address']['zone'] = $zone_info['name'];
				$this->session->data['shipping_address']['zone_code'] = $zone_info['code'];
			} else {
				$this->session->data['shipping_address']['zone'] = '';
				$this->session->data['shipping_address']['zone_code'] = '';
			}

			if (isset($this->request->post['custom_field'])) {
				$this->session->data['shipping_address']['custom_field'] = $this->request->post['custom_field']['address'];
			} else {
				$this->session->data['shipping_address']['custom_field'] = array();
			}

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}