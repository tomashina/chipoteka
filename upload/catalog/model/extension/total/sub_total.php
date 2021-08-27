<?php
class ModelExtensionTotalSubTotal extends Model {
	public function getTotal($total) {
		$this->load->language('extension/total/sub_total');

		$sub_total = $this->cart->getSubTotal();

        if ($this->cart->getSubTotal() && (isset($this->session->data['creditcardname'] ) && $this->session->data['creditcardname']!='' )  && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0000' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0200' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0300' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0400' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0500' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0600' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0700' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0800' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='0900' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='1000' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='1100' ) && (isset($this->session->data['paymentplan'] ) && $this->session->data['paymentplan']!='1200' )) {


            $pricecalculate = $this->config->get('total_low_order_fee_fee') /100  * $this->cart->getSubTotal();

            $sub_total = $this->cart->getSubTotal();

            $sub_total += $pricecalculate;
        }

		if (!empty($this->session->data['vouchers'])) {
			foreach ($this->session->data['vouchers'] as $voucher) {
				$sub_total += $voucher['amount'];
			}
		}

		$total['totals'][] = array(
			'code'       => 'sub_total',
			'title'      => $this->language->get('text_sub_total'),
			'value'      => $sub_total,
			'sort_order' => $this->config->get('total_sub_total_sort_order')
		);

		$total['total'] += $sub_total;
	}
}
