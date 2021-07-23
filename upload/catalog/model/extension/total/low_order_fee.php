<?php
class ModelExtensionTotalLowOrderFee extends Model {
	public function getTotal($total) {
		if ($this->cart->getSubTotal() && (isset($this->session->data['creditcardname'] ) && $this->session->data['creditcardname']!='' )  && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0000' )) {
			$this->load->language('extension/total/low_order_fee');


			$pricecalculate = $this->config->get('total_low_order_fee_fee') /100  * $this->cart->getSubTotal();

			$total['totals'][] = array(
				'code'       => 'low_order_fee',
				'title'      => $this->language->get('text_low_order_fee'),
				'value'      => $pricecalculate,
				'sort_order' => $this->config->get('total_low_order_fee_sort_order')
			);

			/*if ($this->config->get('total_low_order_fee_tax_class_id')) {
				$tax_rates = $this->tax->getRates($this->config->get('total_low_order_fee_fee'), $this->config->get('total_low_order_fee_tax_class_id'));

				foreach ($tax_rates as $tax_rate) {
					if (!isset($total['taxes'][$tax_rate['tax_rate_id']])) {
						$total['taxes'][$tax_rate['tax_rate_id']] = $tax_rate['amount'];
					} else {
						$total['taxes'][$tax_rate['tax_rate_id']] += $tax_rate['amount'];
					}
				}
			}*/

			$total['total'] += $pricecalculate;
		}
	}
}