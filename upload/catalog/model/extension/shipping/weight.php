<?php
class ModelExtensionShippingWeight extends Model {
	public function getQuote($address) {
		$this->load->language('extension/shipping/weight');

		$quote_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "geo_zone ORDER BY name");

        $weight = $this->cart->getWeight();

       $user_id = $this->customer->getId();


        $box = true;

        $forbiden = ['052203', '020802', '020801', '011803', '900440', '900411', '900413', '900412', '900600', '9004ZG', '9004MP', '9004MZ', '9004MH', '9004OS', '9004PU', '9004RI', '9004ST','9004SI', '9004ZD', '900400', '033204', '011902', '052601', '011906', '011907', '011928', '011926', '011925', '100000', '020201', '023103', '023104', '052704', '011914', '011935', '052901', '020608', '100002', '020501', '020203', '011802', '020202', '052701', '052204', '021302', '052802', '052702', '043204', '052801', '052201', '011905', '100001', '011805', '900006', '033406', '052202', '011801', '000001', '000002', '052703', '011901'];

        foreach ($this->cart->getProducts() as $product) {
            $this->load->model('catalog/product');
            $this->load->model('catalog/category');

            $catids = $this->model_catalog_product->getCategories($product['product_id']);

            foreach ($catids as $catid) {
                $cat = $this->model_catalog_category->getCategory($catid['category_id']);
                if (in_array((string)$cat['luceed_uid'], $forbiden, true)) {
                    $box = false;
                }

            }
        }

     /* if( $user_id != '3412'){
            $box = false;
        }*/

		foreach ($query->rows as $result) {
			if ($this->config->get('shipping_weight_' . $result['geo_zone_id'] . '_status')) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$result['geo_zone_id'] . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

				if ($query->num_rows) {
					$status = true;
				} else {
					$status = false;
				}
			} else {
				$status = false;
			}

            if($box){
                $status = true;
            }else{
                $status = false;
            }


            if ($this->cart->getSubTotal() > 500) {
                $status = false;
            }

			if ($status) {
				$cost = '';

				$rates = explode(',', $this->config->get('shipping_weight_' . $result['geo_zone_id'] . '_rate'));

				foreach ($rates as $rate) {
					$data = explode(':', $rate);

					if ($data[0] >= $weight) {
						if (isset($data[1])) {
							$cost = $data[1];
						}

						break;
					}
				}

				if ((string)$cost != '') {
					$quote_data['weight_' . $result['geo_zone_id']] = array(
						'code'         => 'weight.weight_' . $result['geo_zone_id'],
						'title'        => 'BOX NOW paketomat',
						'cost'         => $cost,
						'tax_class_id' => $this->config->get('shipping_weight_tax_class_id'),
						'text'         => $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_weight_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
					);
				}
			}
		}

		$method_data = array();

		if ($box) {
			$method_data = array(
				'code'       => 'weight',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_weight_sort_order'),
				'error'      => false
			);
		}

		return $method_data;
	}
}
