<?php
class ModelExtensionShippingFlat extends Model {
	function getQuote($address) {
		$this->load->language('extension/shipping/flat');

        if ($this->customer->isLogged()) {
            $data['groupId'] = $this->customer->getGroupId();

        } else {
            $data['groupId'] ='0';
        }

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_flat_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if (!$this->config->get('shipping_flat_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$quote_data = array();

            if($data['groupId']>2) {


                if ($this->cart->getSubTotal() < 1000) {
                    $shipping_price = $this->config->get('shipping_flat_cost');
                } else {
                    $shipping_price = 0;
                }

            }else{

                if ($this->cart->getSubTotal() < $this->config->get('shipping_free_total')) {
                    $shipping_price = $this->config->get('shipping_flat_cost');
                } else {
                    $shipping_price = 0;
                }


            }

			$quote_data['flat'] = array(
				'code'         => 'flat.flat',
				'title'        => $this->language->get('text_description'),
				'cost'         => $shipping_price,
				'tax_class_id' => $this->config->get('shipping_flat_tax_class_id'),
				'text'         => $this->currency->format($this->tax->calculate($shipping_price, $this->config->get('shipping_flat_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
			);

			$method_data = array(
				'code'       => 'flat',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_flat_sort_order'),
				'error'      => false
			);
		}

		return $method_data;
	}
}