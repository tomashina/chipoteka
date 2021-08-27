<?php
class ModelExtensionTotalLowOrderFee extends Model {
	public function getTotal($total) {
		if ($this->cart->getSubTotal() && (isset($this->session->data['creditcardname'] ) && $this->session->data['creditcardname']!='' )  && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0000' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0200' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0300' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0400' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0500' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0600' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0700' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0800' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0900' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='1000' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='1100' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='1200' )) {
			$this->load->language('extension/total/low_order_fee');


			$pricecalculate = $this->config->get('total_low_order_fee_fee') /100  * $this->cart->getSubTotal();

			$total['total'] += $pricecalculate;
		}
	}
}