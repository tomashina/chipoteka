<?php
class ModelExtensionPaymentCOD extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/cod');

        if ($this->customer->isLogged()) {
            $data['groupId'] = $this->customer->getGroupId();

        } else {
            $data['groupId'] ='0';
        }

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_cod_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('payment_cod_total') > 0 && $this->config->get('payment_cod_total') < $total) {
			$status = false;
		} elseif (!$this->cart->hasShipping()) {
			$status = false;
		}
        elseif ($data['groupId'] > 2) {
            $status = false;
        }
		elseif (!$this->config->get('payment_cod_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

        if($this->session->data['shipping_method']['code'] == 'flat.flat'){
             $shippingtitle = $this->language->get('text_title');
        }
        else{
            $shippingtitle = 'Plaćanje u poslovnici prilikom preuzimanja';
            $status = true;
        }


		if ($status) {
			$method_data = array(
				'code'       => 'cod',
				'title'      => $shippingtitle,
				'terms'      => '',
				'sort_order' => $this->config->get('payment_cod_sort_order')
			);
		}

		return $method_data;
	}
}
