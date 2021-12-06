<?php
class ControllerAccountSubaccountList extends Controller {
	private $error = array();

	public function index() {
		if (!$this->customer->isLogged()) {
			$this->response->redirect($this->url->link('common/home', '', true));
		}

        if ($this->customer->isLogged()) {
            $data['groupId'] = $this->customer->getGroupId();

        } else {
            $data['groupId'] ='0';
        }

		$this->load->language('account/subaccountlist');

         $data['oib'] = $this->customer->getOib();
        $data['tvrtka'] = $this->customer->getTvrtka();

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

		$this->load->model('account/customer');
        $this->load->model('account/address');

        $data['customers'] = array();

        $results = $this->model_account_customer->getCustomersByOib($data['oib']);

        foreach ($results as $result) {

            $data['customers'][] = array(
                'firstname' => $result['firstname'],
                'lastname' => $result['lastname'],
                'email' => $result['email'],
                'telephone' => $result['telephone'],
                'date_added' => $result['date_added'],
                'edit'     => $this->url->link('account/subaccountchangepass', 'customer_id=' . $result['customer_id'], true)
            );


        }



		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_register'),
			'href' => $this->url->link('account/subaccountlist', '', true)
		);

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');


		$this->response->setOutput($this->load->view('account/subaccountlist', $data));
	}



}