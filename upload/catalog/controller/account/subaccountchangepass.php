<?php
class ControllerAccountSubaccountchangepass extends Controller {
    private $error = array();

    public function index() {


        if ($this->customer->isLogged()) {
            $data['groupId'] = $this->customer->getGroupId();

        } else {
            $data['groupId'] ='0';
        }
        $this->load->model('account/address');
        $data['addresses'] = array();
        $results = $this->model_account_address->getAddresses();

        foreach ($results as $result) {
                if ($result['address_format']) {
                    $format = $result['address_format'];
                } else {
                    $format = '{firstname}' . ", " . '{address_1}' . ", " . '{city}' . ", " .'{postcode}' . ", " . '{country}';
                }

                $find = array(
                    '{firstname}',
                    '{company}',
                    '{address_1}',
                    '{address_2}',
                    '{city}',
                    '{postcode}',
                    '{zone}',
                    '{zone_code}',
                    '{country}'
                );

                $replace = array(
                    'firstname' => $result['firstname'],
                    'company'   => $result['company'],
                    'address_1' => $result['address_1'],
                    'address_2' => $result['address_2'],
                    'city'      => $result['city'],
                    'postcode'  => $result['postcode'],
                    'zone'      => $result['zone'],
                    'zone_code' => $result['zone_code'],
                    'country'   => $result['country']
                );






            $data['addresses'][] = array(
                'address_id' => $result['address_id'],
                'address'    => str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format)))),
                'update'     => $this->url->link('account/address/edit', 'address_id=' . $result['address_id'], true),
                'delete'     => $this->url->link('account/address/delete', 'address_id=' . $result['address_id'], true)
            );
        }

        $this->load->model('account/customer');

       $data['customer_id'] = $this->request->post['customer_id'];

        $data['val'] = $this->request->get['val'];

       $customer_info = $this->model_account_customer->getCustomer($this->request->post['customer_id']);

        $data['logout'] = $this->url->link('account/logout', '', true);

        $data['customer_info'] = $customer_info;

        if ($customer_info ) {

        $this->load->language('account/password');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->model('account/customer');

            $this->model_account_customer->editPassword($customer_info['email'], $this->request->post['password']);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('account/subaccountlist', '', true));
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
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('account/password', '', true)
        );

        if (isset($this->error['password'])) {
            $data['error_password'] = $this->error['password'];
        } else {
            $data['error_password'] = '';
        }

        if (isset($this->error['confirm'])) {
            $data['error_confirm'] = $this->error['confirm'];
        } else {
            $data['error_confirm'] = '';
        }

        $data['action'] = $this->url->link('account/subaccountchangepass', '', true);

        if (isset($this->request->post['password'])) {
            $data['password'] = $this->request->post['password'];
        } else {
            $data['password'] = '';
        }

        if (isset($this->request->post['confirm'])) {
            $data['confirm'] = $this->request->post['confirm'];
        } else {
            $data['confirm'] = '';
        }

        $data['back'] = $this->url->link('account/account', '', true);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('account/subaccountchangepass', $data));

        } else {
            $this->response->setOutput($this->load->view('account/subaccountchangepass', $data));
        }
    }

    protected function validate() {


                if ((utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) > 40) ) {

                        $this->error['password'] = $this->language->get('error_password');

                }


                if ($this->request->post['confirm'] != $this->request->post['password']) {
                    $this->error['confirm'] = $this->language->get('error_confirm');
                }



                return !$this->error;


    }
}
