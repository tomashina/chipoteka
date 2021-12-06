<?php
class ControllerAccountSubaccount extends Controller {
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

        $this->load->language('account/subaccount');
        $data['logout'] = $this->url->link('account/logout', '', true);
        $data['oib'] = $this->customer->getOib();
        $data['tvrtka'] = $this->customer->getTvrtka();

        $this->document->setTitle($this->language->get('heading_title'));

        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
        $this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

        $this->load->model('account/customer');


        if (isset($this->request->post['customeradd'])) {
            $data['customeradd'] = $this->request->post['customeradd'];
        } else {
            $data['customeradd'] = '';
        }


        $data['customers'] = array (
            0  => array (
                "partner_uid" => "51916-1063",
                "naziv" => "VACOM - Daruvar",
                "adresa" => "Petra Svačića 20",
                "naziv_mjesta" => "DARUVAR",
                "postanski_broj" => "43500",
                "e_mail" => "daruvar@va.com.hr",
                "telefon" => "043/445-446",
                "grupa_partnera"=> "00225431"
            ),
            1  => array (
                "partner_uid" => "56706-1063",
                "naziv" => "VACOM - Sisak",
                "adresa" => "Rimska 8",
                "naziv_mjesta" => "SISAK",
                "postanski_broj" => "44000",
                "e_mail"=> "sisak@va.com.hr",
                "telefon" => "044 540388",
                "grupa_partnera"=> "00225431"
            )
        );


        foreach($data['customers'] as $entry) {
            if($entry['partner_uid'] == $data['customeradd'])
                $newArr[] = $entry;
        }



        $this->request->post['email'] = $newArr[0]['e_mail'];
        $this->request->post['telephone'] = $newArr[0]['telefon'];
        $this->request->post['firstname'] = $newArr[0]['naziv'];
        $this->request->post['lastname'] = $newArr[0]['naziv_mjesta'];



        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $data['add_customer'] = $this->model_account_customer->addCustomer($this->request->post);



            $this->model_account_address->addAddress($this->customer->getId(), $this->request->post);

            // Clear any previous login attempts for unregistered accounts.
            $this->model_account_customer->deleteLoginAttempts($this->request->post['email']);

            $this->customer->login($this->request->post['email'], $this->request->post['password']);

            unset($this->session->data['guest']);

            $this->response->redirect($this->url->link('account/success'));
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
            'href' => $this->url->link('account/subaccount', '', true)
        );
        $data['text_account_already'] = sprintf($this->language->get('text_account_already'), $this->url->link('account/login', '', true));

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

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

        if (isset($this->error['customer'])) {
            $data['error_customer'] = $this->error['customer'];
        } else {
            $data['error_customer'] = '';
        }

        $data['action'] = $this->url->link('account/subaccount', '', true);

        $data['customer_groups'] = array();

        if (is_array($this->config->get('config_customer_group_display'))) {
            $this->load->model('account/customer_group');

            $customer_groups = $this->model_account_customer_group->getCustomerGroups();

            foreach ($customer_groups as $customer_group) {
                if (in_array($customer_group['customer_group_id'], $this->config->get('config_customer_group_display'))) {
                    $data['customer_groups'][] = $customer_group;
                }
            }
        }

        $data['customer_group_id'] = $data['groupId'];



        if (isset($this->request->post['customeradd'])) {


            $data['firstname'] = $newArr[0]['naziv'];
        } else {
            $data['firstname'] = '';
        }

        if (isset($this->request->post['customeradd'])) {
            $data['lastname'] =  $newArr[0]['naziv_mjesta'];
        } else {
            $data['lastname'] = '';
        }

        if (isset($this->request->post['customeradd'])) {
            $data['email'] = $newArr[0]['e_mail'];
            $this->request->post['email'] = $newArr[0]['e_mail'];
        } else {
            $data['email'] = '';
        }

        if (isset($this->request->post['customeradd'])) {
            $data['telephone'] = $newArr[0]['telefon'];
        } else {
            $data['telephone'] = '';
        }

        // Custom Fields
        $data['custom_fields'] = array();

        $this->load->model('account/custom_field');

        $custom_fields = $this->model_account_custom_field->getCustomFields();

        foreach ($custom_fields as $custom_field) {
            if ($custom_field['location'] == 'account') {
                $data['custom_fields'][] = $custom_field;
            }
        }

        if (isset($this->request->post['custom_field']['account'])) {
            $data['register_custom_field'] = $this->request->post['custom_field']['account'];
        } else {
            $data['register_custom_field'] = array();
        }

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

        if (isset($this->request->post['newsletter'])) {
            $data['newsletter'] = $this->request->post['newsletter'];
        } else {
            $data['newsletter'] = '';
        }

        // Captcha
        if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
            $data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
        } else {
            $data['captcha'] = '';
        }

        if ($this->config->get('config_account_id')) {
            $this->load->model('catalog/information');

            $information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

            if ($information_info) {
                $data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_account_id'), true), $information_info['title']);
            } else {
                $data['text_agree'] = '';
            }
        } else {
            $data['text_agree'] = '';
        }

        if (isset($this->request->post['agree'])) {
            $data['agree'] = $this->request->post['agree'];
        } else {
            $data['agree'] = false;
        }

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('account/subaccount', $data));
    }




    private function validate() {


        if ($this->model_account_customer->getTotalCustomersByEmail($this->request->post['email'])) {
            $this->error['warning'] = $this->language->get('error_exists');
        }




        // Customer Group
        if (isset($this->request->post['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->post['customer_group_id'], $this->config->get('config_customer_group_display'))) {
            $customer_group_id = $this->request->post['customer_group_id'];
        } else {
            $customer_group_id = $this->config->get('config_customer_group_id');
        }

        // Custom field validation
        $this->load->model('account/custom_field');

        $custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

        foreach ($custom_fields as $custom_field) {
            if ($custom_field['location'] == 'account') {
                if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
                    $this->error['custom_field'][$custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                } elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
                    $this->error['custom_field'][$custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                }
            }
        }

        if ((utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
            $this->error['password'] = $this->language->get('error_password');
        }

        if ($this->request->post['customeradd'] == '') {
            $this->error['customer'] = $this->language->get('error_customer');
        }

        if ($this->request->post['confirm'] != $this->request->post['password']) {
            $this->error['confirm'] = $this->language->get('error_confirm');
        }

        // Captcha
        if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
            $captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

            if ($captcha) {
                $this->error['captcha'] = $captcha;
            }
        }

        // Agree to terms
        if ($this->config->get('config_account_id')) {
            $this->load->model('catalog/information');

            $information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

            if ($information_info && !isset($this->request->post['agree'])) {
                $this->error['warning'] = sprintf($this->language->get('error_agree'), $information_info['title']);
            }
        }

        return !$this->error;
    }


    public function customfield() {
        $json = array();

        $this->load->model('account/custom_field');

        // Customer Group
        if (isset($this->request->get['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->get['customer_group_id'], $this->config->get('config_customer_group_display'))) {
            $customer_group_id = $this->request->get['customer_group_id'];
        } else {
            $customer_group_id = $this->config->get('config_customer_group_id');
        }

        $custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

        foreach ($custom_fields as $custom_field) {
            $json[] = array(
                'custom_field_id' => $custom_field['custom_field_id'],
                'required'        => $custom_field['required']
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}