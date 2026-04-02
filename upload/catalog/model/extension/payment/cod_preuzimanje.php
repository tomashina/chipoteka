<?php
class ModelExtensionPaymentCodPreuzimanje extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/cod_preuzimanje');

        if ($this->customer->isLogged()) {
            $data['groupId'] = $this->customer->getGroupId();

        } else {
            $data['groupId'] ='0';
        }

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_cod_preuzimanje_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");


        $data['cartData'] = $this->cart->getProducts();

		if ($this->config->get('payment_cod_preuzimanje_total') > 0 && $this->config->get('payment_cod_preuzimanje_total') < $total) {
			$status = false;
		} elseif (!$this->cart->hasShipping()) {
			$status = false;
		}
        elseif ($data['groupId'] > 2) {
            $status = false;
        }
        elseif ($this->searchInArray($data['cartData'], "Televizor", "name") && $this->searchInArray($data['cartData'], "98incha", "name") || $this->searchInArray($data['cartData'], "85incha", "name")   || $this->searchInArray($data['cartData'], "77incha", "name")  || $this->searchInArray($data['cartData'], "75incha", "name")  || $this->searchInArray($data['cartData'], "65incha", "name")  || $this->searchInArray($data['cartData'], "55incha", "name")  || $this->searchInArray($data['cartData'], "58incha", "name")) {

            if($this->searchInArray($data['cartData'], "NOKIA", "name") || $this->searchInArray($data['cartData'], "LUXOR", "name") || $this->searchInArray($data['cartData'], "STRONG", "name")){
                $status = true;
            } else{
                $status = false;
            }

        }
		elseif (!$this->config->get('payment_cod_preuzimanje_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		}






        else {
			$status = false;
		}

     

        if (isset($this->session->data['shipping_method']['code']) && $this->session->data['shipping_method']['code'] == 'weight.weight_5' and ($this->cart->getSubTotal() > 200)) {
            $status = false;
        }

        if (isset($this->session->data['shipping_method']['code']) && $this->session->data['shipping_method']['code'] == 'flat.flat') {
            $status = false;
        }




		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'cod_preuzimanje',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_cod_preuzimanje_sort_order')
			);
		}

		return $method_data;
	}

    public function searchInArray($rawArray, $search, $column) {
        foreach ($rawArray as $value) {
            if (isset($value[$column]) && strpos($value[$column], $search) !== false) {
                return true;
            }
        }

        return false;
    }
}
