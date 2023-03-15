<?php
class ControllerExtensionShippingXshippingpro extends Controller {
     use OCM\Traits\Back\Controller\Common;
     use OCM\Traits\Back\Controller\Crud;
     use OCM\Traits\Back\Controller\Product;
     use OCM\Traits\Back\Controller\Util;
     private $ext_path;
     private $ext_key;
     private $error = array();
     private $ocm;
     private $meta = array(
        'id'       => '13705',
        'type'     => 'shipping',
        'name'     => 'xshippingpro',
        'path'     => 'extension/shipping/',
        'title'    => 'X-Shippingpro',
        'version'  => '4.1.3',
        'ocmod'    => true,
        'event'    => true
    );
    /* Config with default values  Special keyword __LANG__ denotes array of languages e.g 'name' => array('__LANG__' => 'xyz') */
    private $setting = array(
        'xshippingpro_status'      => '',
        'xshippingpro_sort_order'  => '',
        'xshippingpro_map_api'     => '',
        'xshippingpro_group'       => 'no_group',
        'xshippingpro_group_limit' => '',
        'xshippingpro_sorting'     => '',
        'xshippingpro_heading'     => array('__LANG__' => 'Shipping Options'),
        'xshippingpro_desc_mail'   => '',
        'xshippingpro_admin_all'   => '',
        'xshippingpro_debug'       => '',
        'xshippingpro_sub_group'   => array(),
        'xshippingpro_sub_group_limit' => array(),
        'xshippingpro_sub_group_name'  => array(),
        'xshippingpro_estimator'   => array('selector' => '#product', 'type' => 'method', 'tab' => false),
        'xshippingpro_selector'    => array('logo' => '', 'desc' => '')
    );
    private $events = array(
        array(
            'trigger' => 'catalog/model/extension/shipping/*/getQuote/after',
            'action'  => 'extension/shipping/xshippingpro/listenQuote'
        ),
        array(
            'trigger' => 'catalog/controller/api/shipping/update_sup_options/before',
            'action'  => 'extension/shipping/xshippingpro/on_update_sup_options'
        ),
        array(
            'trigger' => 'catalog/controller/api/shipping/get_sup_options/before',
            'action'  => 'extension/shipping/xshippingpro/on_get_sup_options'
        )
    );
    private $tables = array(
        'xshippingpro' => array (
            array('name'=> 'sort_order', 'option' => 'int(8) NULL')
        )
    );
    public function __construct($registry) {
        parent::__construct($registry);
        $this->ocm = new OCM\Back($registry, $this->meta);
        $this->ext_path = $this->meta['path'] . $this->meta['name'];
        $this->ext_key = 'model_' . str_replace('/', '_', $this->ext_path);
        $this->events[] = array(
            'trigger' => 'catalog/view/mail/order'.(VERSION >= '3.0.0.0' ? '_add' : '').'/before',
            'action'  => $this->ext_path . '/onOrderEmail'
        );
    }
    public function index() {
        $this->load->language($this->ext_path);
        $ext_lang = $this->load->language($this->ext_path);
        $this->load->model($this->ext_path);
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        /* Some help lang modificaiton */
        $ext_lang['help_time'] = sprintf($this->language->get('help_time'), date('h:i:s A'));
        $ext_lang['help_date'] = sprintf($this->language->get('help_date'), date('Y-m-d'));
        $data = array();
        $data = array_merge($data, $ext_lang);

        /* লাইসেন্স বেরিফিকেসন  */
        $rs = $this->ocm->rpd();
        $data['_v'] = $rs ? '' : $this->ocm->vs();
        /* লাইসেন্স শেষ */
        $this->ocm->checkOCMOD();
        $this->upgrade();

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            if (isset($this->request->files['file_import']['tmp_name'])) {
                $this->import();
                $this->response->redirect($this->ocm->url->getExtensionURL());
            }
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->ocm->url->getExtensionsURL());
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['x_name'] = $this->meta['name'];
        $data['x_path'] = $this->meta['path'] . $this->meta['name'];

       if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        if (isset($this->session->data['warning'])) {
            $data['error_warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        }
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->ocm->url->link('common/dashboard', '', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->ocm->url->getExtensionsURL()
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->ocm->url->getExtensionURL()
        );

        $data['action'] = $this->ocm->url->getExtensionURL();
        $data['cancel'] = $this->ocm->url->getExtensionsURL();
        $data['export'] = $this->ocm->url->link($this->ext_path . '/export', '', true);

        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();
        $data['languages'] = $this->ocm->url->getLangImage($languages);
        $data['language_id'] = $this->config->get('config_language_id');

        $data['sub_groups_count'] = 10;
        $data['method_data'] = $this->{$this->ext_key}->getData();
        /* All required options */
        $options = $this->getFormBuilderOptions($data);
        /* set form data */
        $this->ocm->form->setLangs($ext_lang)->setOptions($options);
        $group_modes = array(
            'no_group' => $this->language->get('text_no_grouping'),
            'lowest'   => $this->language->get('text_lowest'),
            'highest'  => $this->language->get('text_highest'),
            'average'  => $this->language->get('text_average'),
            'sum'      => $this->language->get('text_sum')
        );
        $data['group_modes'] = $group_modes;

        $placeholders = array(
            '{subTotal}'                   => $this->language->get('text_eq_cart_total'),
            '{subTotalWithTax}'            => $this->language->get('text_eq_cart_total_tax'),
            '{weight}'                     => $this->language->get('text_eq_cart_weight'),
            '{quantity}'                   => $this->language->get('text_eq_cart_qnty'),
            '{volume}'                     => $this->language->get('text_eq_cart_vol'),
            '{dimension}'                  => $this->language->get('text_eq_cart_dimension'),
            '{dimensional}'                => $this->language->get('text_eq_dimension'),
            '{volumetric}'                 => $this->language->get('text_eq_volumetric'),
            '{subTotalAsPerProductRule}'   => $this->language->get('text_eq_method_total'),
            '{subTotalWithTaxAsPerProductRule}' => $this->language->get('text_eq_method_total_tax'),
            '{weightAsPerProductRule}'     => $this->language->get('text_eq_method_weight'),
            '{quantityAsPerProductRule}'   => $this->language->get('text_eq_method_qnty'),
            '{volumeAsPerProductRule}'     => $this->language->get('text_eq_method_vol'),
            '{dimensionAsPerProductRule}'  => $this->language->get('text_eq_method_dimension'),
            '{shipping}'                   => $this->language->get('text_eq_shipping'),
            '{modifier}'                   => $this->language->get('text_eq_modifier'),
            '{vouchers}'                   => $this->language->get('text_percent_vouchers'),
            '{special}'                    => $this->language->get('text_percent_special'),
            '{noOfPackage}'                => $this->language->get('text_eq_no_package'),
            '{noOfProduct}'                => $this->language->get('text_eq_no_product'),
            '{noOfCategory}'               => $this->language->get('text_eq_no_cat'),
            '{noOfLocation}'               => $this->language->get('text_eq_no_loc'),
            '{noOfManufacturer}'           => $this->language->get('text_eq_no_man'),
            '{noOfOutOfStockProduct}'      => $this->language->get('text_eq_stock_product'),
            '{noOfProductAsPerProductRule}' => $this->language->get('text_eq_no_product_method'),
            '{noOfFreeProduct}'            => $this->language->get('text_eq_no_free_product'),
            '{weightOfFreeProduct}'        => $this->language->get('text_eq_weight_free_product'),
            '{totalOfGiftProduct}'         => $this->language->get('text_eq_total_gift_product'),
            '{weightOfGiftProduct}'        => $this->language->get('text_eq_weight_gift_product'),
            '{noOfBlock}'                  => $this->language->get('text_eq_no_block'),
            '{blockPriceAsc}'              => $this->language->get('text_eq_block_asc'),
            '{blockPriceDesc}'             => $this->language->get('text_eq_block_desc'),
            '{couponValue}'                => $this->language->get('text_eq_coupon'),
            '{rewardValue}'                => $this->language->get('text_eq_reward'),
            '{grandTotal}'                 => $this->language->get('text_grand_total'),
            '{grandBeforeShipping}'        => $this->language->get('text_grand_total_before_shiping'),
            '{nonShippableCost}'           => $this->language->get('text_eq_non_shippable'),
            '{highest}'                    => $this->language->get('text_eq_highest'),
            '{lowest}'                     => $this->language->get('text_eq_lowest'),
            '{highestQnty}'                => $this->language->get('text_eq_highest_qnty'),
            '{lowestQnty}'                 => $this->language->get('text_eq_lowest_qnty'),
            '{distance}'                   => $this->language->get('text_rate_type_distance'),
            '{nonMethodSub}'               => $this->language->get('text_eq_non_method_sub'),
            '{nonMethodQnty}'              => $this->language->get('text_eq_non_method_qnty'),
            '{minHeight}, {maxHeight}, {sumHeight}' => $this->language->get('text_eq_height'),
            '{minWidth}, {maxWidth}, {sumWidth}'    => $this->language->get('text_eq_width'),
            '{minLength}, {maxLength}, {sumLength}' => $this->language->get('text_eq_length'),
            '{productWidth}, {productHeight}, {productLength}, {productWeight}, {productQuantity}, {productPrice}, {productVolume}'    => $this->language->get('text_eq_all'),
            '{anyProductWidth}, {anyProductHeight}, {anyProductLength}, {anyProductWeight}, {anyProductQuantity}, {anyProductPrice}, {anyProductSpecialPrice}, {anyProductVolume}' => $this->language->get('text_eq_any'),
        );
        // external shipping modules
        $shippings = $this->ocm->misc->getShippingMethods($data['language_id'], array(), false, true);
        $_shippings = array();
        $data['shippings'] = array();
        foreach ($shippings as $each) {
            $_shippings['{mod'.$each['value'].'}'] = $each['name'];
            $data['shippings'][$each['value']] = $each['name'];
        }
        $shipping_placeholders = '';
        if ($shippings) {
            $shipping_placeholders .= $this->ocm->misc->getHelpTag($data['text_eq_other_mod']); 
            $shipping_placeholders .= $this->getPlaceholderList($_shippings);
        }
        $data['additional_methods'] = json_encode($shippings);

        $more_help = array();
        $eq_placeholders = $this->getPlaceholderList($placeholders);
        $more_help['equation'] = $eq_placeholders . $this->ocm->misc->getHelpTag($data['more_equation']) . $this->ocm->misc->getHelpTag($data['text_eq_group']) . $this->ocm->misc->getHelpTag($data['text_eq_xfeepro']) . $shipping_placeholders;
        $more_help['zone'] = $data['more_zone'];
        $more_help['error'] = $data['more_error'];
        $more_help['dimensional_factor'] = $data['more_dimensional_factor'];
        $data['more_help'] = json_encode($more_help);
        
        $data['oc_3_1'] = VERSION >= '3.1.0.0';
        $data['global'] = $this->getConfigForm($data);
        $data['tpl'] = json_encode(array(
            'method'     =>  $this->getFormData($data, true),
            'sub_option' => $this->getSubOptionForm($data, array(), true)
        ));
        $data['methods'] = $this->getMethodList($data['method_data']);
        $data['form_data'] = $this->getFormData($data);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->ocm->view($this->ext_path, $data));
    }
    private function getFormBuilderOptions($data) {
        $options = array();
        $this->load->model('localisation/tax_class'); 
        $tax_classes = $this->model_localisation_tax_class->getTaxClasses();
        array_unshift($tax_classes, array('tax_class_id' => 0,'name' => $this->language->get('text_none')));
        $options['tax_class_id'] = $this->ocm->form->getOptions($tax_classes, 'tax_class_id');

        $this->load->model('localisation/geo_zone');
        $geo_zones = $this->model_localisation_geo_zone->getGeoZones();
        $options['geo_zone'] = $this->ocm->form->getOptions($geo_zones, 'geo_zone_id');

        $this->load->model('setting/store');
        $stores = $this->model_setting_store->getStores();
        array_unshift($stores, array('store_id' => 0,'name' => $this->language->get('text_store_default')));
        $options['store'] = $options['estimator_store'] = $this->ocm->form->getOptions($stores, 'store_id');

        $this->load->model('localisation/currency');
        $currencies = $this->model_localisation_currency->getCurrencies();
        $options['currency'] = $this->ocm->form->getOptions($currencies, 'currency_id');
      
        $cg_path = (VERSION >= '2.1.0.1') ? 'customer' : 'sale';
        $this->load->model($cg_path . '/customer_group');
        $customer_groups = $this->{'model_' . $cg_path . '_customer_group'}->getCustomerGroups();
        $customer_groups[] = array('customer_group_id' => 0, 'name' => $this->language->get('text_guest_checkout'));
        $options['customer_group'] = $this->ocm->form->getOptions($customer_groups, 'customer_group_id');

        $this->load->model('localisation/country');
        $countries = $this->model_localisation_country->getCountries();
        $options['country'] = $this->ocm->form->getOptions($countries, 'country_id');

        $options['payment'] = $this->ocm->misc->getPaymentMethods($data['language_id']);
        $status_options = array('1' => $data['text_enabled'], '0' => $data['text_disabled']);
        $options['status'] = $options['debug'] = $this->ocm->form->getOptions($status_options, 'none');

        $visibility_options = array('1' => $data['text_visiable'], '0' => $data['text_hidden']);
        $options['visibility'] = $this->ocm->form->getOptions($visibility_options, 'none');

        $inc_exc_options = array('inclusive' => $data['text_rule_inclusive'], 'exclusive' => $data['text_rule_exclusive']);
        $inc_exc_options = $this->ocm->form->getOptions($inc_exc_options, 'none');
        $options['city_rule'] = $options['coupon_rule'] = $options['postal_rule'] = $options['customer_rule'] = $inc_exc_options; 

        $product_rule_options = array(
            '' => $data['text_any'],
            '6' => $data['text_ones_any'],
            '3' => $data['text_ones_any_with_other'],
            '4' => $data['text_ones_must'],
            '2' => $data['text_ones_must_with_other'],
            '5' => $data['text_ones_except'],
            '7' => $data['text_ones_except_with_other'] 
        );
        $product_rule_options = $this->ocm->form->getOptions($product_rule_options, 'none');
        $options['category'] = $options['product'] = $options['option'] = $options['attribute'] = $options['manufacturer_rule'] = $options['location_rule'] = $product_rule_options; 

        $rate_type_options = array(
            'flat'            => $data['text_rate_flat'],
            'quantity'        => $data['text_rate_quantity'],
            'weight'          => $data['text_rate_weight'],
            'volume'          => $data['text_rate_volume'],
            'dimension'       => $data['text_rate_dimension'],
            'dimensional'     => $data['text_dimensional_weight'],
            'volumetric'      => $data['text_volumetric_weight'],
            'sub'             => $data['text_rate_sub_total'],
            'total'           => $data['text_rate_total'],
            'sub_coupon'      => $data['text_rate_sub_coupon'],
            'sub_negative'    => $data['text_rate_negative_item'],
            'total_coupon'    => $data['text_rate_total_coupon'],
            'grand_shipping'  => $data['text_grand_total_before_shiping'],
            'grand'           => $data['text_grand_total'],
            'grand_wtax'      => $data['text_percent_grand_wo_tax'],
            'per_manufacturer' => $data['text_per_manufacturer'],
            'product'         => $data['text_rate_type_product'],
            'no_package'      => $data['text_no_of_package'],
            'no_product'      => $data['text_no_of_product'],
            'no_category'     => $data['text_no_of_category'],
            'no_manufacturer' => $data['text_no_of_manufacturers'],
            'no_location'     => $data['text_no_of_location'],
            'distance'        => $data['text_rate_type_distance'],
            'equation'        => $data['text_type_equation']
        );
        $options['rate_type'] = $this->ocm->form->getOptions($rate_type_options, 'none');
        $percent_options = array(
            'sub'             => $data['text_percent_sub_total'],
            'total'           => $data['text_percent_total'],
            'sub_shipping'    => $data['text_percent_sub_total_shipping'],
            'total_shipping'  => $data['text_percent_total_shipping'],
            'shipping'        => $data['text_percent_shipping']
        );
        $options['rate_percent'] = $this->ocm->form->getOptions($percent_options, 'none');
        $week_day_optios = array(
            '0' => $data['text_sunday'],
            '1' => $data['text_monday'],
            '2' => $data['text_tuesday'],
            '3' => $data['text_wednesday'],
            '4' => $data['text_thursday'],
            '5' => $data['text_friday'],
            '6' => $data['text_saturday']
        );
        $options['days'] = $this->ocm->form->getOptions($week_day_optios, 'none');
        $final_cost = array(
            'single' => $data['text_final_single'],
            'cumulative' => $data['text_final_cumulative']
        );
        $options['rate_final'] = $this->ocm->form->getOptions($final_cost, 'none');
        $modes = array(
            '0' => $data['text_mode_and'],
            '1' => $data['text_mode_or']
        );
        $options['product_or'] = $this->ocm->form->getOptions($modes, 'none');
        $sorting = array(
            '1' => $data['text_sort_manual'],
            '2' => $data['text_sort_price_asc'],
            '3' => $data['text_sort_price_desc'],
            '4' => $data['text_sort_name_asc'],
            '5' => $data['text_sort_name_desc']
        );
        $options['sorting'] = $this->ocm->form->getOptions($sorting, 'none');
        $estimator_fields = array(
            'country' => $data['text_estimator_country'],
            'zone' => $data['text_estimator_zone'],
            'postal' => $data['text_estimator_postal']
        );
        $options['estimator_fields'] = $this->ocm->form->getOptions($estimator_fields, 'none');
        $estimator_type = array(
            'method' => $data['text_type_method'],
            'avail' => $data['text_type_avail']
        );
        $options['estimator_type'] = $this->ocm->form->getOptions($estimator_type, 'none');
        
        $subtitle_type = array(
            'main'          => $data['text_sub_title_main'],
            'main_sub_last' => $data['text_sub_title_main_sub_last'],
            'sub'           => $data['text_sub_title'],
            'sub_last'      => $data['text_sub_title_sub_last'],
        );
        $options['sub_title'] = $this->ocm->form->getOptions($subtitle_type, 'none');

        $custom_fields = $this->getCustomFields();
        $options['custom'] = $this->ocm->form->getOptions($custom_fields, 'custom_field_value_id');

        //x-level membership
        if ($this->isXLevelAvailable()) {
            $xlevel_path = VERSION >= '2.3.0.0' ? 'extension/module/xlevel' : 'module/xlevel';
            $xlevel_key = 'model_' . str_replace('/', '_', $xlevel_path);
            $this->load->model($xlevel_path);
            $levels = $this->{$xlevel_key}->getLevels();
            foreach ($levels as &$level) {
                $name = isset($level['name'][$data['language_id']]) ? $level['name'][$data['language_id']] : 'Untitled level';
                $level['name'] = $name;
            }
            $options['xlevel'] = $this->ocm->form->getOptions($levels, 'level_id');
        }

        $group = array();
        $group[] = array(
            'name' => 'None',
            'value' => 0
        );
        for ($i=1; $i <= $data['sub_groups_count']; $i++) {
            $group[] = array(
                'name' => 'Group' . $i,
                'value' => $i
            );
        }
        $options['group'] = $group;

        $group_limit = array('1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5);
        $options['group_limit'] = $this->ocm->form->getOptions($group_limit, 'none');
        return $options;
    }
    public function load_method() {
       $ext_lang = $this->load->language($this->ext_path);
       /* Some help lang modificaiton */
       $ext_lang['help_time'] = sprintf($this->language->get('help_time'), date('h:i:s A'));
       $ext_lang['help_date'] = sprintf($this->language->get('help_date'), date('Y-m-d'));
       
       $this->load->model($this->ext_path);
       $json = array();
       $data = array();
       $data = array_merge($data, $ext_lang);
       $tab_id = isset($this->request->post['tab_id']) ? $this->request->post['tab_id'] : 0;

       $this->load->model('localisation/language');
       $languages = $this->model_localisation_language->getLanguages();
       $data['languages'] = $this->ocm->url->getLangImage($languages);
       $data['language_id'] = $this->config->get('config_language_id');
       $data['sub_groups_count'] = 10;
       $data['export'] = $this->ocm->url->link($this->ext_path . '/export', '', true);
       $data['method_data'] = $this->{$this->ext_key}->getData();
       $data['methods'] = $this->getMethodList($data['method_data']);
       // exteranl modules
        $shippings = $this->ocm->misc->getShippingMethods($data['language_id'], array(), false, true);
        $data['shippings'] = array();
        foreach ($shippings as $each) {
            $data['shippings'][$each['value']] = $each['name'];
        }
       //reset method_data with the  method that need to be loaded
       $data['method_data'] = array($this->{$this->ext_key}->getDataByTabId($tab_id));
       $options = $this->getFormBuilderOptions($data);
       $this->ocm->form->setLangs($ext_lang)->setOptions($options);
       $json['html'] = $tab_id ? $this->getFormData($data) : '';
       $json['tab_id'] = $tab_id;
       $this->response->addHeader('Content-Type: application/json');
       $this->response->setOutput(json_encode($json)); 
    }
    public function export() {
        $this->load->model($this->ext_path);
        $content = '';
        $filename = $this->meta['name'] . '.txt';
        $type = 'text/x-csv';
        if (isset($this->request->get['no'])) {
            $dest = isset($this->request->get['dest']) ? $this->request->get['dest'] : 'price';
            $method_row = $this->{$this->ext_key}->getDataByTabId($this->request->get['no']);
            if ($method_row) {
                if ($dest == 'option') {
                    $sub_options = array();
                    $fields = array('{cost}');
                    $field_updated = false;
                    if (empty($method_row['method_data']['sub_options'])) {
                        $method_row['method_data']['sub_options'] = array();
                        $this->load->model('localisation/language');
                        $languages = $this->model_localisation_language->getLanguages();
                        foreach ($languages as $language) {
                            array_unshift($fields, '{name}['.$language['language_id'].']');
                        }
                    }
                    foreach ($method_row['method_data']['sub_options'] as $each) {
                        $sub_option = array();
                        foreach($each['name'] as $language_id => $name) {
                            if (!$field_updated) {
                                array_unshift($fields, '{name}['.$language_id.']');
                            }
                            $sub_option['name['.$language_id.']'] = $name;
                        }
                        $sub_option['cost'] = $each['cost'];
                        $sub_options[] = $sub_option;
                        $field_updated = true;
                    }
                    $content = $this->ocm->misc->getCSV($sub_options, $fields);
                } else {
                    $fields = array();
                    if (empty($method_row['method_data']['ranges'])) {
                        $method_row['method_data']['ranges'] = array();
                        $fields = array('{start}','{end}', '{cost}','{block}','{partial}');
                    }
                    foreach ($method_row['method_data']['ranges'] as $each) {
                        foreach($each as $field => $value) {
                            if ($field == 'product_id' && !$value) {
                                continue;
                            }
                            if ($field == 'type' && !$each['product_id']) {
                                continue;
                            }
                            $fields[] = '{' . $field . '}';    
                        }
                        break;
                    }
                    $content = $this->ocm->misc->getCSV($method_row['method_data']['ranges'], $fields);
                }
                $filename = $method_row['method_data']['display'];
                if (!$filename) {
                    $filename = $this->getLangField($method_data, 'name');
                }
                if ($dest == 'option') {
                    $filename .= '_sub-options';
                }
                $filename .= '.csv';
            }
        } else {
            $export = array();
            $export['method_data'] = $this->{$this->ext_key}->getData();
            $setting = $this->ocm->setting->getSetting($this->setting);
            $export = array_merge($export, $setting);
            $content = json_encode($export);
            $type = 'text/txt';
        }
        $this->forceDownload($content, $filename, $type);
    }
    public function import() {
        $this->load->model('setting/setting');
        $this->load->model($this->ext_path);
        $success = false;
        if ($this->request->server['REQUEST_METHOD'] == 'POST' && is_uploaded_file($this->request->files['file_import']['tmp_name']) && file_exists($this->request->files['file_import']['tmp_name'])) {
            $import_data = file_get_contents($this->request->files['file_import']['tmp_name']);
            if ($import_data) {
                $import_data = $this->{$this->ext_key}->getUnCompressedData($import_data);
                if (is_array($import_data) && (isset($import_data['method_data']) || isset($import_data[$this->meta['name']]))) {
                    $method_data = array();
                    if (isset($import_data['method_data']) && is_array($import_data['method_data'])) {
                        $method_data = $import_data['method_data'];
                        unset($import_data['method_data']);
                    }
                    /* Add prefix if imported from OC 2.x */
                    foreach ($import_data as $key => $value) {
                        if ($this->ocm->prefix && strpos($key, $this->ocm->prefix) === false) {
                            $import_data[$this->ocm->prefix . $key] = $value;
                        }
                    }
                    /* Save global */
                    $this->request->post = array_merge($import_data, $this->request->post);
                    $save = $this->ocm->setting->editSetting($this->setting);
                    $this->model_setting_setting->editSetting($save['key'], $save['value']);
                    /* add shipping methods */
                    foreach($method_data as $single) {
                        $single['method_data'] = json_encode($single['method_data']);
                        $this->{$this->ext_key}->addData($single);
                    }
                    $success = true;
                }
            }
        }
        if ($success) {
            $this->session->data['success'] = $this->language->get('text_success');
        } else {
            $this->session->data['warning'] = $this->language->get('error_import');
        }
    }
    public function importCSV(){
        ini_set('auto_detect_line_endings', true);
        $this->load->language($this->ext_path);
        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();
        $json = array();
        if (!empty($this->request->files['file']['name'])) {
            $filename = basename(preg_replace('/[^a-zA-Z0-9\.\-\s+]/', '', html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8')));
            $allowed =  array('csv');
            if (!in_array(substr(strrchr($filename, '.'), 1), $allowed)) {
                $json['error'] = $this->language->get('error_filetype');
            }
            if ($this->request->files['file']['error'] != UPLOAD_ERR_OK) {
                $json['error'] = $this->language->get('error_partial');
            }
        } else {
            $json['error']=$this->language->get('error_upload');  
        }

        $dest = isset($this->request->post['dest']) ? $this->request->post['dest'] : 'price';

        if ($dest == 'price') {
            $heading = array('start' => 0,'end' => 0,'product_id' => -1,'cost' => 0,'block' => 0, 'partial' => 0, 'type' => -1, 'name' => 0);
        } else {
            $heading = array('name' => 0, 'cost' => 1);  
        }
        
        $heading_found = false;

        if (!$json && is_uploaded_file($this->request->files['file']['tmp_name']) && file_exists($this->request->files['file']['tmp_name'])) {
            $isFound = false;
            $multi_lingual_csv = true;
            $json['data'] = array();
            $json['dest'] = $dest;
            $name_indices = array();
            if (($handle = fopen($this->request->files['file']['tmp_name'], "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (!$heading_found && strpos($data[0], '{') !== false && strpos($data[0], '}') !== false) {
                        foreach ($data as $index => $field) {
                            foreach ($heading as $key => $value) {
                                if ('{'.$key.'}' == trim($field)) {
                                    $heading[$key] = $index;
                                    $heading_found = true;
                                } else if (preg_match('/.*\[(\d+)\]/', trim($field), $matches)) {
                                    $name_indices[$index] = $matches[1];
                                }
                            }
                        }
                        continue;
                    }

                    if (!$heading_found) continue;
                    $row = array();
                    // multi-lingual name, let's sort them first.
                    if ($dest == 'option' && !$name_indices) {
                        foreach($languages as $languages) {
                            $name_indices[] = $languages['language_id'];
                        }
                        $multi_lingual_csv = false;
                    }
                    if ($name_indices) {
                        $row['name'] = array();
                        foreach ($name_indices as $index => $language_id) {
                            $row['name'][$language_id] = $multi_lingual_csv ? $data[$index] : $data[0];    
                        }
                    }
                    foreach ($heading as $key => $index) {
                        if ($name_indices && $key == 'name') continue; // multi-lingual so ignore..
                        $row[$key] = isset($data[$index]) ? $data[$index] : '';
                    }
                    if (empty($row['name'])) $row['name'] = 'Will show on saved & reload';
                    if ($row) {
                        $json['data'][] = $row; 
                        $isFound = true;
                    }
                }
                fclose($handle);
            }
            if (!$isFound) $json['error'] = $this->language->get('error_no_data');
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json)); 
    }
    private function getConfigForm($data) {
        /* Set base name for form inputs */
        $setting = $this->ocm->setting->getSetting($this->setting, $data['languages']);
        $this->ocm->form->setBasename($this->ocm->prefix . 'xshippingpro_', 'prefix');
        $this->ocm->form->setPreset($setting)->setIDPostfix('');

        $return = '';
        $return .= $this->ocm->misc->getLangTabs('language_heading', $data['languages']);
        $active = ' active';
        $return .= '<div class="tab-content">';
        foreach ($data['languages'] as $language) { 
            $language_id = $language['language_id'];
            $return .= '<div class="tab-pane' . $active . '" id="language_heading' . '-' . $language_id . '">';

            $param = array(
                'name'  => 'heading[' . $language_id . ']',
                'required' => true
            );
            $return .= $this->ocm->form->get('input', $param);

            $return .= '</div>';
            $active = '';
        }
        $return .= '</div>';

        $tabs = array(
            'global-general' => $data['tab_global_general'],
            'global-group' => $data['tab_group_option'],
            'global-estimator' => $data['tab_estimator'],
            'global-selector' => $data['tab_selector'],
            'global-export' => $data['tab_import_export'],
            'global-help' => $data['tab_help']
        );
        $return .= $this->ocm->misc->getTabs('global-tab', $tabs);
        $return .= '<div class="tab-content">';

        $return .= '<div class="tab-pane active" id="global-general">';
        $return .= $this->ocm->form->get('input', array('name' => 'sort_order', 'help' => $data['help_module_sort_order']));
        $return .= $this->ocm->form->get('checkbox', array('name' => 'desc_mail', 'label' => $data['text_desc_mail']));
        $return .= $this->ocm->form->get('checkbox', array('name' => 'admin_all', 'label' => $data['text_admin_all']));
        $return .= $this->ocm->form->get('input', 'map_api');
        $return .= $this->ocm->form->get('select', 'sorting');
        $return .= $this->ocm->form->get('select', array('name' => 'status', 'title' => $data['entry_module_status'], 'help' => $data['help_module_status']));
        $return .= $this->ocm->form->get('select', 'debug');
        $return .= '</div>';

        $return .= '<div class="tab-pane" id="global-group">';
        $table_body = $this->getSubGroups($data, $setting);
        $table_headings = array(
            array(
                'title'   => $data['text_group_id']
            ),
            array(
                'title'   => $data['text_group_type']
            ),
            array(
                'title' => $data['text_group_limit'],
                'help'  => $data['help_group_limit']
            ),
            array(
                'title'   => $data['entry_group_name'],
                'help'  => $data['help_group_name']
            )
        );
        $element = $this->ocm->misc->getTableSkeleton($table_headings, $table_body);
        $return .= $this->ocm->form->get('bare', array('name' => 'method_group', 'element' => $element, 'label_col' => 12));

        $return .= $this->ocm->form->get('select', array( 'name' => 'group', 'title' => $data['entry_group_shipping_mode'], 'options' => $this->ocm->form->getOptions($data['group_modes'], 'none'), 'help' => $data['help_group_in_group']));

        $group = $setting[$this->ocm->prefix . 'xshippingpro_group'];
        $visible = $group == 'lowest' || $group == 'highest';
        $return .= $this->ocm->form->get('select', array('name' => 'group_limit', 'class' => 'ocm-hide group lowest highest', 'visible' => $visible));
        
        $return .= '</div>';

        $return .= '<div class="tab-pane" id="global-estimator">';
        $return .= $this->ocm->misc->getHelpTag($data['text_estimator_module']);
        $return .= $this->ocm->form->get('checkbox', array('name' => 'estimator[status]', 'label' => $data['text_estimator']));
        $return .= $this->ocm->form->get('checkbox', array('name' => 'estimator[tab]', 'label' => $data['text_estimator_tab']));
        $return .= $this->ocm->form->get('checkgroup', array('name' => 'estimator[store][]', 'all' => false));
        $return .= $this->ocm->form->get('checkgroup', array('name' => 'estimator[fields][]', 'all' => false));
        $return .= $this->ocm->form->get('select', 'estimator[type]');
        $return .= $this->ocm->form->get('input', 'estimator[selector]');
        $return .= $this->ocm->form->get('textarea', 'estimator[css]');
        $return .= '</div>';

        $return .= '<div class="tab-pane" id="global-selector">';
        $return .= $this->ocm->misc->getHelpTag($data['help_selector_detail']);
        $return .= $this->ocm->form->get('input', 'selector[logo]');
        $return .= $this->ocm->form->get('input', 'selector[desc]');
        $return .= '</div>';

        $return .= '<div class="tab-pane" id="global-export">';
        $element = '<input type="file" class="form-control ocm-import" id="input-import" accept="text/txt" name="file_import" />&nbsp<button type="submit" form="form-ocm" data-toggle="tooltip" name="action" value="import" class="btn btn-primary">'.$data['text_import'].'</button>';
        $return .= $this->ocm->form->get('bare', array('name' => 'import', 'element' => $element));
        $element = '<a href="' . $data['export'] . '" target="_blank" class="btn btn-primary">' . $data['text_export'] . '</a>';
        $return .= $this->ocm->form->get('bare', array('name' => 'export', 'element' => $element));
        $return .= '</div>';

        $return .= '<div class="tab-pane" id="global-help">';
        $return .= '<div class="ocm-debug-button"><a class="btn btn-danger" href="javascript:debugBrowser();" role="button">'.$data['text_debug_button'].'</a></div>';
        $return .= $this->ocm->misc->getOCMInfo();
        $return .= '</div>';
        $return .= '</div>';
        return $return;
    }
    private function getFormData($data, $new_tab = false) {
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('catalog/option');
        $this->load->model('catalog/manufacturer');
        $cg_path = (VERSION >= '2.1.0.1') ? 'customer' : 'sale';
        $this->load->model($cg_path . '/customer');

        if ($new_tab) {
            $data['method_data'] = array(
                array('tab_id' => '__INDEX__', 'method_data' => array())
            );
        }
        /* Set base name for form inputs */
        $this->ocm->form->setBasename($this->meta['name']);
        $range_types = array('quantity', 'weight', 'volume', 'dimension', 'dimensional', 'volumetric', 'sub', 'total', 'sub_negative', 'sub_coupon', 'total_coupon', 'grand_shipping', 'grand', 'no_package', 'no_product', 'no_category', 'no_manufacturer', 'no_location', 'distance', 'equation', 'per_manufacturer');
        $non_total_types = array('flat','quantity', 'weight', 'volume', 'dimension', 'dimensional', 'volumetric', 'no_package', 'no_product', 'no_category', 'no_manufacturer', 'no_location', 'distance', 'equation', 'per_manufacturer');
        
        $fields_lang = array(
            'name'  => 'Untitled Item',
            'desc'  => '',
            'error' => ''
        );
        $fields_all = array(
            'store'          => 'store_all',
            'geo_zone'       => 'geo_zone_all',
            'zone'           => 'zone_all',
            'country'        => 'country_all', 
            'currency'       => 'currency_all',
            'customer_group' => 'customer_group_all',
            'payment'        => 'payment_all',
            'city'           => 'city_all',
            'postal'         => 'postal_all',
            'coupon'         => 'coupon_all',
            'days'           => 'days_all',
            'customers'      => 'customer_all',
            'xlevel'         => 'xlevel_all',
            'custom'         => 'custom_all'
        );
        $default_values = $this->getDefaultValues();
        $return = '';
        $count = 0;
        foreach($data['method_data'] as $single_method) {
            $count++;
            $no_of_tab   = $single_method['tab_id'];
            $method_data = $single_method['method_data'];
            $method_data = $this->resetEmptyAll($method_data, $fields_all);
            $method_data = array_merge($default_values, $method_data);
            $method_data = $this->setDefaultByLangs($method_data, $data['languages'], $fields_lang);
            if (!$method_data['display']) {
                $method_data['display'] = $this->getLangField($method_data, 'name');
            }
            // move this in commmon place in future
            if ($no_of_tab == '__INDEX__') {
                $method_data['method_specific'] = 1;
            }
            $this->ocm->form->setPreset($method_data)->setIDPostfix($no_of_tab);

            $return .= '<div id="ocm-method-'.$no_of_tab.'" class="tab-pane xshippingpro ocm-method">';
            if (!$new_tab && $count > 1) {
               $return .= '</div>';
               continue; 
            }
            $return .= '<div class="ocm-action-btn">';
            $return .= $this->ocm->misc->getButton(array('type' => 'success', 'help'=> $data['text_method_save'], 'class' => 'btn-ocm-save btn-sm', 'icon' => 'fa-save'));
            $return .= $this->ocm->misc->getButton(array('type' => 'warning', 'help'=> $data['text_method_copy'], 'class' => 'btn-ocm-copy btn-sm', 'icon' => 'fa-copy'));
            $return .= $this->ocm->misc->getButton(array('type' => 'danger', 'help'=> $data['text_method_remove'], 'class' => 'btn-ocm-delete btn-sm', 'icon' => 'fa-trash fa-trash-alt'));
            $return .= '</div>';

            $return .= $this->ocm->form->get('input', 'display');

            $return .= $this->ocm->misc->getLangTabs('language_' . $no_of_tab, $data['languages']);

            $active = ' active';
            $return .= '<div class="tab-content">';
            foreach ($data['languages'] as $language) { 
                $language_id = $language['language_id'];
                $return .= '<div class="tab-pane' . $active . '" id="language_' . $no_of_tab . '-' . $language_id . '">';

                $param = array(
                    'name'  => 'name[' . $language_id . ']',
                    'required' => true
                );
                $return .= $this->ocm->form->get('input', $param);

                $param = array(
                    'name'  => 'mask[' . $language_id . ']'
                );
                $return .= $this->ocm->form->get('input', $param);

                $param = array(
                    'name'  => 'desc[' . $language_id . ']',
                    'rows'  => 2
                );
                $return .= $this->ocm->form->get('textarea', $param);

                $param = array(
                    'name'    => 'error[' . $language_id . ']',
                    'class'   => 'ocm-hide',
                    'attr'    => 'disable',
                    'visible' => !!$method_data['disable'],
                    'rows'    => 2
                );
                $return .= $this->ocm->form->get('textarea', $param);

                $return .= '</div>';
                $active = '';
            }
            $return .= '</div>';

            $tabs = array(
                'common_' . $no_of_tab      => $data['tab_general'],
                'criteria_' . $no_of_tab    => $data['tab_criteria_setting'],
                'catprod_' . $no_of_tab     => $data['tab_category_product'],
                'price_' . $no_of_tab       => $data['tab_price_setting'],
                'sub-options_' . $no_of_tab => $data['tab_sub_options'],
                'event_' . $no_of_tab => $data['tab_condition_event']
            );
            $return .= $this->ocm->misc->getTabs('method-tab' . $no_of_tab, $tabs);

            $return .= '<div class="tab-content">';
            $return .= '<div class="tab-pane active" id="common_' . $no_of_tab . '">';
            $element = '<div class="ocm-id">' . $no_of_tab . '</div>';
            $return .= $this->ocm->form->get('bare', array('name' => 'id', 'element' => $element));
            $return .= $this->ocm->form->get('checkbox', 'inc_weight');
            $return .= $this->ocm->form->get('checkbox', 'exc_vat');
            $return .= $this->ocm->form->get('select', 'tax_class_id');
            $return .= $this->ocm->form->get('input', 'logo');
            $return .= $this->ocm->form->get('input', 'sort_order');
            $return .= $this->ocm->form->get('select', 'visibility');
            $return .= $this->ocm->form->get('select', 'status');
            $return .= $this->ocm->form->get('select', 'group');
            $return .= '</div>';
            $return .= '<div class="tab-pane" id="criteria_'.$no_of_tab.'">';

            $zones = $this->getZones($method_data['country']);
            $zones = $this->ocm->form->getOptions($zones, 'zone_id');

            $return .= $this->ocm->form->get('checkgroup', 'store[]');
            $return .= $this->ocm->form->get('checkgroup', array('name' => 'geo_zone[]', 'search' => true));
            $return .= $this->ocm->form->get('checkgroup', 'customer_group[]');
            $return .= $this->ocm->form->get('checkgroup', array('name' => 'country[]', 'search' => true));
            $return .= $this->ocm->form->get('checkgroup', array('name' => 'zone[]', 'options' => $zones, 'search' => true));
            $return .= $this->ocm->form->get('checkgroup', 'currency[]');
            $return .= $this->ocm->form->get('checkgroup', 'payment[]');
            $return .= $this->ocm->form->get('checkgroup', array('name' => 'custom[]', 'search' => true));
            if ($this->isXLevelAvailable()) {
               $return .= $this->ocm->form->get('checkgroup', 'xlevel[]'); 
            }

            /* cunstomers */
            $customers = array();
            foreach ($method_data['customers'] as $customer_id) {
                $customer_info = $this->{'model_' . $cg_path . '_customer'}->getCustomer($customer_id);
                if ($customer_info) {
                    $name = strip_tags(html_entity_decode($customer_info['firstname'], ENT_QUOTES, 'UTF-8')) . ' ' . strip_tags(html_entity_decode($customer_info['lastname'], ENT_QUOTES, 'UTF-8')) . ' (ID: '.$customer_id.')';
                    $customers[] = array(
                        'customer_id' => $customer_id,
                        'name'  => $name
                    );
                }
            }
            /* Customer */
            $visible = !$method_data['customer_all'];
            $customers = $this->ocm->form->getOptions($customers, 'customer_id');
            $groups = array();
            $groups['checkbox'] = array('name' => 'customer_all', 'label' => $data['text_any']);
            $groups['autofill'] = array('name' => 'customers[]', 'attr'  => 'customer_all', 'options' => $customers, 'visible' => $visible);
            $groups['select'] = array('name' => 'customer_rule', 'attr' => 'customer_all', 'visible' => $visible);
            $return .= $this->ocm->form->getFrom($groups);
            /* end of customers */

            /* City */
            $visible = $method_data['city_all'] != '1';
            $groups = array();
            $groups['checkbox'] = array('name' => 'city_all', 'label' => $data['text_any']);
            $groups['textarea'] = array('name' => 'city', 'attr' => 'city_all', 'visible' => $visible);
            $groups['select'] = array('name' => 'city_rule', 'attr' => 'city_all', 'visible' => $visible);
            $return .= $this->ocm->form->getFrom($groups);
            /* end of city */

            /* Postal */
            $visible = $method_data['postal_all'] != '1';
            $groups = array();
            $groups['checkbox'] = array('name' => 'postal_all', 'label' => $data['text_any']);
            $groups['textarea'] = array('name' => 'postal', 'attr' => 'postal_all', 'visible' => $visible);
            $groups['select'] = array('name' => 'postal_rule', 'attr' => 'postal_all', 'visible' => $visible);
            $return .= $this->ocm->form->getFrom($groups);
            /* end of postal */

            /* Coupon */
            $visible = $method_data['coupon_all'] != '1';
            $groups = array();
            $groups['checkbox'] = array('name' => 'coupon_all', 'label' => $data['text_any']);
            $groups['textarea'] = array('name' => 'coupon', 'attr' => 'coupon_all', 'visible' => $visible);
            $groups['select'] = array('name' => 'coupon_rule', 'attr' => 'coupon_all', 'visible' => $visible);
            $return .= $this->ocm->form->getFrom($groups);
            /* end of postal */

            $return .= $this->ocm->form->get('checkgroup', 'days[]');
            $return .= $this->ocm->form->get('range', array('name' => 'time', 'type' => 'time'));
            $return .= $this->ocm->form->get('range', array('name' => 'date', 'type' => 'date'));
            $return .= $this->hookRules($method_data);

            $return .= '</div>';
            $return .= '<div class="tab-pane" id="catprod_'.$no_of_tab.'">';
            $return .=$this->ocm->misc->getHelpTag($data['text_product_rules']); 
            /* categories */
            $categories = array();
            foreach ($method_data['product_category'] as $category_id) {
               $category_info = $this->model_catalog_category->getCategory($category_id);
                if ($category_info) {
                    if ($category_info['path']) $category_info['path'] .=  '&nbsp;&nbsp;&gt;&nbsp;&nbsp;';
                    $categories[] = array(
                        'category_id' => $category_id,
                        'name'       => $category_info['path'].$category_info['name']
                    );
                }
            }

            $visible = (int)$method_data['category'] > 1;
            $return .= $this->ocm->form->get('select', 'category');
            $param = array(
                'name'  => 'product_category[]',
                'options' => $this->ocm->form->getOptions($categories, 'category_id'),
                'attr'  => 'category',
                'browser' => 'category',
                'visible' => $visible
            );
            $return .= $this->ocm->form->get('autofill', $param);

            /* Products  */
            $products = array();
            foreach ($method_data['product_product'] as $product_id) {
               $product_info = $this->model_catalog_product->getProduct($product_id);
               if ($product_info) {
                    $products[] = array(
                        'product_id' => $product_id,
                        'name'       => $product_info['name']
                    );
               }
            }
            
            $visible = (int)$method_data['product'] > 1;
            $return .= $this->ocm->form->get('select', 'product');
            $param = array(
                'name'  => 'product_product[]',
                'options' => $this->ocm->form->getOptions($products, 'product_id'),
                'attr'  => 'product',
                'browser' => 'product',
                'visible' => $visible
            );
            $return .= $this->ocm->form->get('autofill', $param);

            /* Product Options  */
            $options = array();
            foreach ($method_data['product_option'] as $option_value_id) {
               $option_value_info = $this->model_catalog_option->getOptionValue($option_value_id);
               if ($option_value_info) {
                    $option_info = $this->model_catalog_option->getOption($option_value_info['option_id']);
                    if ($option_info) {
                        $options[] = array(
                            'option_value_id' => $option_value_id,
                            'name'            => strip_tags(html_entity_decode($option_info['name'], ENT_QUOTES, 'UTF-8')).'&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . strip_tags(html_entity_decode($option_value_info['name'], ENT_QUOTES, 'UTF-8'))
                        );
                    }
               }
            }
            
            $visible = (int)$method_data['option'] > 1;
            $return .= $this->ocm->form->get('select', 'option');
            $param = array(
                'name'  => 'product_option[]',
                'options' => $this->ocm->form->getOptions($options, 'option_value_id'),
                'attr'  => 'option',
                'visible' => $visible
            );
            $return .= $this->ocm->form->get('autofill', $param);

            /* Product attribute  */
            $options = array();
            foreach ($method_data['product_attribute'] as $attribute_id) {
                $attribute = $this->{$this->ext_key}->getAttribute($attribute_id);
                if ($attribute) {
                    $options[] = array(
                        'attribute_id' => $attribute_id,
                        'name'         => strip_tags(html_entity_decode($attribute['attribute_group'], ENT_QUOTES, 'UTF-8')) . '&nbsp;&nbsp;&gt;&nbsp;&nbsp;' . strip_tags(html_entity_decode($attribute['name'], ENT_QUOTES, 'UTF-8'))
                    );
                }
            }
            
            $visible = (int)$method_data['attribute'] > 1;
            $return .= $this->ocm->form->get('select', 'attribute');
            $param = array(
                'name'  => 'product_attribute[]',
                'options' => $this->ocm->form->getOptions($options, 'attribute_id'),
                'attr'  => 'attribute',
                'visible' => $visible
            );
            $return .= $this->ocm->form->get('autofill', $param);

            /* Manufacturer  */
            $manufacturers = array();
            foreach ($method_data['manufacturer'] as $manufacturer_id) {
                $manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($manufacturer_id);
                if ($manufacturer_info) {
                    $manufacturers[] = array(
                        'manufacturer_id' => $manufacturer_id,
                        'name'            => $manufacturer_info['name']
                    );
                }
            }

            $visible = (int)$method_data['manufacturer_rule'] > 1;
            $return .= $this->ocm->form->get('select', 'manufacturer_rule');
            $param = array(
                'name'    => 'manufacturer[]',
                'options' => $this->ocm->form->getOptions($manufacturers, 'manufacturer_id'),
                'attr'    => 'manufacturer_rule',
                'browser' => 'manufacturer',
                'visible' => $visible
            );
            $return .= $this->ocm->form->get('autofill', $param);

            /* Locations  */
            $locations = array();
            foreach ($method_data['location'] as $location) {
                if ($location) {
                    $locations[] = array(
                        'value'  => $location,
                        'name'   => $location
                    );
                }
            }

            $visible = (int)$method_data['location_rule'] > 1;
            $return .= $this->ocm->form->get('select', 'location_rule');
            $param = array(
                'name'  => 'location[]',
                'options' => $locations,
                'attr'  => 'location_rule',
                'visible' => $visible
            );
            $return .= $this->ocm->form->get('autofill', $param);

            $return .=$this->ocm->misc->getHelpTag($data['text_product_rules_advanced']);
            $return .= $this->ocm->form->get('select', 'product_or');

            $return .= $this->ocm->form->get('checkbox', array('name' => 'ingore_product_rule', 'label' => $data['text_ingore_product']));
            $return .= '</div>';

            $return .= '<div class="tab-pane" id="price_'.$no_of_tab.'">';

            $return .= $this->ocm->form->get('select', 'rate_type');
            $visible = $method_data['rate_type']=='dimensional' || $method_data['rate_type']=='volumetric';
            $return .= $this->ocm->form->get('input', array('name' =>'dimensional_factor', 'visible' => $visible, 'class' => 'rate_type ocm-hide volumetric dimensional'));
            $return .= $this->ocm->form->get('checkbox', array('name' =>'dimensional_overfule', 'visible' => $visible, 'class' => 'rate_type ocm-hide volumetric dimensional'));

            $return .= $this->ocm->form->get('checkbox', array('name' => 'method_specific', 'label' => $data['text_method_specific']));

            $return .= $this->ocm->form->get('input', array('name' => 'cost', 'visible' => $method_data['rate_type']=='flat', 'class' => 'rate_type ocm-hide flat'));

            $element = $this->getRanges($method_data, $data, $no_of_tab);
            $return .= $this->ocm->form->get('bare', array('name' => 'unit_range', 'label_col' => 0, 'element' => $element, 'visible' => $method_data['rate_type'] != 'flat', 'class' => 'rate_type ocm-hide product ' . implode(' ', $range_types)));

            $splits = array('additional', 'additional_per', 'additional_limit');
            $element = $this->ocm->misc->getSplittedInput($splits, $method_data, $data, $this->meta['name']);
            $return .= $this->ocm->form->get('bare', array('name' => 'additional', 'element' => $element, 'visible' => $method_data['rate_type'] != 'flat', 'class' => 'rate_type ocm-hide product ' . implode(' ', $range_types)));

            $visible =  in_array($method_data['rate_type'], $non_total_types); 
            $return .= $this->ocm->form->get('range', array('name' =>'order_total', 'visible' => $visible, 'class' => 'rate_type ocm-hide ' . implode(' ', $non_total_types)));
            
            $visible = $method_data['rate_type'] != 'weight' && $method_data['rate_type'] != 'product';
            $return .= $this->ocm->form->get('range', array('name' =>'weight', 'visible' => $visible, 'class' => 'rate_type ocm-hide flat ' . implode(' ', array_diff($range_types, array('weight')))));
            
            $visible = $method_data['rate_type'] != 'quantity' && $method_data['rate_type'] != 'product';
            $return .= $this->ocm->form->get('range', array('name' =>'quantity', 'visible' => $visible, 'class' => 'rate_type ocm-hide flat ' . implode(' ', array_diff($range_types, array('quantity')))));

            $splits = array('max_length', 'max_width', 'max_height', 'max_weight');
            $element = $this->ocm->misc->getSplittedInput($splits, $method_data, $data, $this->meta['name']);
            $return .= $this->ocm->form->get('bare', array('name' => 'max_package_dim', 'element' => $element));

            $return .= $this->ocm->form->get('input', array('name' => 'cart_adjust', 'visible' => $method_data['rate_type'] != 'flat', 'class' => 'rate_type ocm-hide product ' . implode(' ', $range_types)));

            $visible = $method_data['rate_type'] != 'flat' && $method_data['rate_type'] != 'product';
            $return .= $this->ocm->form->get('select', array('name' => 'rate_final', 'visible' => $visible, 'class' => 'rate_type ocm-hide ' . implode(' ', $range_types)));

            $return .= $this->ocm->form->get('select', 'rate_percent');
            
            $splits = array('rate_min', 'rate_max', 'rate_add');
            $element = $this->ocm->misc->getSplittedInput($splits, $method_data, $data, $this->meta['name']);
            $return .= $this->ocm->form->get('bare', array('name' => 'price_adjustment', 'element' => $element, 'visible' => $method_data['rate_type'] != 'flat', 'class' => 'rate_type ocm-hide product ' . implode(' ', $range_types)));

            $return .= $this->ocm->form->get('textarea', 'equation');
            $return .= $this->ocm->form->get('checkbox', array('name' => 'equation_neg', 'label' => $data['text_equation_neg']));
            $return .= '</div>';

            $return .= '<div class="tab-pane" id="event_'.$no_of_tab.'">';

            $return .= $this->ocm->form->get('checkbox', array('name' => 'disable', 'label' => $data['text_shipping_disable']));
            $return .= $this->ocm->form->get('checkbox', array('name' => 'disable_other', 'label' => $data['text_disable_other']));

            // Event On Active
            $hides = array();
            foreach ($method_data['hide'] as $hide_tab_id) {
                if (isset($data['methods'][$hide_tab_id]) || isset($data['shippings'][$hide_tab_id])) {
                    $name = isset($data['methods'][$hide_tab_id]) ? $data['methods'][$hide_tab_id] : $data['shippings'][$hide_tab_id];
                    $hides[] = array(
                        'value' => $hide_tab_id,
                        'name'  => $name
                    );
                }
            }
            $param = array(
                'name'  => 'hide[]',
                'attr' => 'hide',
                'options' => $hides,
                'class' => 'ocm-visible'
            );
            $return .= $this->ocm->form->get('autofill', $param);

            // Event On Inactive
            $hides = array();
            foreach ($method_data['hide_inactive'] as $hide_tab_id) {
                if (isset($data['methods'][$hide_tab_id]) || isset($data['shippings'][$hide_tab_id])) {
                    $name = isset($data['methods'][$hide_tab_id]) ? $data['methods'][$hide_tab_id] : $data['shippings'][$hide_tab_id];
                    $hides[] = array(
                        'value' => $hide_tab_id,
                        'name'  => $name
                    );
                }
            }
            $param = array(
                'name'  => 'hide_inactive[]',
                'attr' => 'hide',
                'options' => $hides,
                'class' => 'ocm-visible'
            );
            $return .= $this->ocm->form->get('autofill', $param);
            $return .= '</div>';

            $return .= '<div class="tab-pane" id="sub-options_' . $no_of_tab . '">';
            $return .= $this->ocm->form->get('select', 'sub_title');
            $return .= $this->ocm->form->get('checkbox', array('name' => 'free_option', 'label' => $data['text_option_free']));
            $return .= $this->ocm->form->get('checkbox', array('name' => 'optional_option', 'label' => $data['text_optional_option']));

            $return .='<div class="ocm-sub-option-buttons">';
            $return .=     $this->ocm->misc->getButton(array('type' => 'danger', 'title'=> $data['text_delete_all'], 'class' => 'ocm-sub-option-remove-all btn-sm'));
            $return .=     $this->ocm->misc->getButton(array('type' => 'primary', 'title'=> $data['text_csv_import'], 'class' => 'sub-option-import-btn btn-sm'));
            $return .= '<a href="' . $data['export'] . '&no='.$no_of_tab.'&dest=option" target="_blank" class="btn btn-primary btn-sm">' . $data['text_export'] . '</a>';
            $return .= '</div>';
            
            $visible_sub_options = 50;
            $sub_options_html =  $this->getSubOptionForm($data, $method_data['sub_options'], false, $visible_sub_options);
            $return .= '<div class="sub-options-container draggable-container">' . $sub_options_html . '</div>';
            if (count($method_data['sub_options']) > $visible_sub_options) {
                $return .= '<div class="reveal-sub-options"><button type="button" class="btn btn-default reveal-sub-option">' . $data['text_reveal_options'] . '</button></div>'; 
            }
            $return .= '<div class="sub-options-btn">';
            $return .= $this->ocm->misc->getButton(array('type' => 'primary', 'title' => $data['text_add_new'], 'class' => 'add-sub-option', 'icon' => 'fa-plus-circle'));
            $return .= '</div>';

            $return .= '</div>';
            /* End of Sub-option tab */
            $return .= '</div>';
            $return .= '</div>';
        }
        
        return $return;
    }
    private function getSubOptionForm($data, $sub_options = array(), $new = false, $visible = 50) {
         $counter = 0;
         if ($new) {
            $sub_options = array(
                array(
                    'cost' => ''
                )
            );
            $counter = '__INDEX__';
         }
         $return = '';
         $hidden = '';
         foreach ($sub_options as $sub_option) {
             if ($counter != '__INDEX__' && $counter > $visible) {
                $hidden = 'option-hidden ';
             }
             $return .= '<div draggable="true" index="'.$counter.'" class="sub-option '.$hidden.'draggable sub-option-'.$counter.'"><table>';
             $return .= '<tr>';
             $return .= '<td>';
             $return .= '<label class="control-label col-form-label">'.$data['text_sub_option_name'].'</label>';
             $return .= '</td>';
             $return .= '<td>';
             $return .= '<table>'; 
             foreach ($data['languages'] as $language) {
                $name = isset($sub_option['name'][$language['language_id']]) ? $sub_option['name'][$language['language_id']] : 'Option Name';
                $return .= '<tr>';
                $return .= '<td width="20"><img src="'.$language['image'].'" title="'.$language['name'].'" /></td>';
                $return .= '<td>';
                $return .= '<input draggable="true" type="text" name="xshippingpro[sub_options]['.$counter.'][name]['.$language['language_id'].']" value="'.$name .'" class="form-control" />';
                $return .= '</td>';
                $return .= '</tr>';
             }
             $return .= '</table>';
             $return .= '</td>';
             $return .= '</tr>';
             $return .= '<tr>';
             $return .= '<td>';
             $return .= '<label class="control-label col-form-label"><span data-toggle="tooltip" data-html="true" title="'.$data['text_sub_option_cost_help'].'">'.$data['text_sub_option_cost'].'</span></label>';
             $return .= '</td>';
             $return .= '<td>';
             $return .= '<input placeholder="'.$data['text_sub_option_placeholder'].'" draggable="true" type="text" name="xshippingpro[sub_options]['.$counter.'][cost]" value="'.$sub_option['cost'].'" class="form-control" />';
             $return .= '</td>';
             $return .= '</tr>';
             $return .= '</table>';
             $return .= '<div class="sub-option-action"><button type="button" class="btn btn-danger btn-sm delete-sub-option"><i class="fa fas fa-times"></i></button><button type="button" class="btn btn-default btn-light btn-sm drag-sub-option"><i class="fa fas fa-arrows-alt drag-pivot"></i></button></div></div>';
             if ($counter == '__INDEX__') {
                $counter = 0;  
             }
             $counter++;
        }
        return $return;
    }
    private function getDefaultValues() {
        return array(
            /* array rules */   
            'customer_group'    => array(),
            'geo_zone'          => array(),
            'product_category'  => array(),
            'product_product'   => array(),
            'store'             => array(),
            'currency'          => array(),
            'payment'           => array(),
            'manufacturer'      => array(),
            'days'              => array(),
            'ranges'            => array(),
            'products'          => array(),
            'country'           => array(),
            'zone'              => array(),
            'name'              => array(),
            'desc'              => array(),
            'product_option'    => array(),
            'product_attribute' => array(),
            'hide'              => array(),
            'hide_inactive'     => array(),
            'location'          => array(),
            'sub_options'       => array(),
            'customers'         => array(),
            'custom'            => array(),
            'xlevel'            => array(),
            /* string/numberic rules*/
            'inc_weight'           => '',
            'dimensional_factor'   => 500,
            'dimensional_overfule' => '',
            'customer_group_all'   => '',
            'geo_zone_all'         => '',
            'country_all'          => '',
            'zone_all'             => '',
            'store_all'            => '',
            'manufacturer_all'     => '',
            'postal_all'           => '',
            'coupon_all'           => '',
            'currency_all'         => '',
            'payment_all'          => '',
            'city_all'             => '',
            'days_all'             => '',
            'customer_all'         => '',
            'custom_all'           => '',
            'xlevel_all'           => '',
            'city'                 => '',
            'postal'               => '',
            'coupon'               => '',
            'city_rule'            => 'inclusive',
            'postal_rule'          => 'inclusive',
            'coupon_rule'          => 'inclusive',
            'customer_rule'        => 'inclusive',
            'time_start'           => '',
            'time_end'             => '',
            'rate_final'           => 'single',
            'rate_percent'         => 'sub',
            'rate_min'             => '',
            'rate_max'             => '',
            'rate_add'             => '',
            'location_rule'     => '',
            'manufacturer_rule' => '',
            'disable'           => '',
            'disable_other'     => '',
            'additional'        => '',
            'additional_per'    => '',
            'additional_limit'  => '',
            'other_product'     => '',
            'other_product_per' => '',
            'logo'              => '',
            'group'             => 'no_group',
            'order_total_start' => '',
            'order_total_end'   => '',
            'weight_start'      => '',
            'weight_end'        => '',
            'quantity_start'    => '',
            'quantity_end'      => '',
            'mask'              => '',
            'equation'          => '',
            'tax_class_id'      => '',
            'option'            => 1,
            'attribute'         => 1,
            'sort_order'        => '',
            'status'            => 1,
            'visibility'        => 1,
            'category'          => '',
            'product'           => '',
            'rate_type'         => 'flat',
            'cost'              => '',
            'display'           => 'Untitled Item',
            'ingore_product_rule' => '',
            'product_or'      => 1,
            'method_specific' => '',
            'free_option'     => '',
            'optional_option' => '',
            'sub_title'       => 'main',
            'date_start'      => '',
            'date_end'        => '',
            'equation_neg'    => '',
            'exc_vat'         => '',
            'max_height'      => '',
            'max_width'       => '',
            'max_length'      => '',
            'cart_adjust'     => 0
        );
    }
    private function getRanges($method_data, $data, $no_of_tab) {
        $fields = array('start', 'end', 'product_id', 'cost', 'block', 'partial', 'type');
        $class = $method_data['rate_type'] == 'product' ? ' product-cols' : ' range-cols';
        $return = '';
        $return .='<div class="ocm-range-container'.$class.'">
                    <div class="ocm-range-option">';
        $return .= '   <div class="price-range">'.$data['entry_unit_range'].'</div>';
        $return .= '   <a target="_blank" href="'.$data['export'].'&no='.$no_of_tab.'" class="btn btn-info export-btn btn-sm range-btn" role="button">'.$data['text_export'].'</a>';
        $return .=     $this->ocm->misc->getButton(array('type' => 'danger', 'title'=> $data['text_delete_all'], 'class' => 'ocm-row-remove-all btn-sm range-btn'));
        $return .=     $this->ocm->misc->getButton(array('type' => 'primary', 'title'=> $data['text_csv_import'], 'class' => 'range-import-btn btn-sm range-btn'));
        $return .= '</div>';

        $ranges = array();
        $product_ids = array();
        foreach ($method_data['ranges'] as $counter => $range) {
            foreach ($fields as $field) {
                if (!isset($range[$field])) {
                    $range[$field] = '';
                }
            }
            $ranges[] = $range;
            if ($range['product_id']) {
                $product_ids[] = $range['product_id'];
            }
        }
        $products = $this->{$this->ext_key}->getBatchProducts($product_ids);
        $table_body = '';
        foreach ($ranges as $counter => $range) {
            $name = '';
            if ($method_data['rate_type'] == 'product') {
                if (!isset($products[$range['product_id']])) {
                    continue;
                }
                $name = $products[$range['product_id']]['name'];
            }
            
            $table_body .= '<tr rel="'.$counter.'">' 
                            .'<td class="text-left ocm-hide product">'.$name.'<input type="hidden" name="xshippingpro[ranges]['.$counter.'][product_id]" value="' . $range['product_id'] . '" /></td>'
                            .'<td class="text-left ocm-hide range"><input size="15" type="text" class="form-control" name="xshippingpro[ranges]['.$counter.'][start]" value="' . $range['start'] . '" /></td>'
                            .'<td class="text-left ocm-hide range"><input size="15" type="text" class="form-control" name="xshippingpro[ranges]['.$counter.'][end]" value="' . $range['end'] . '" /></td>'
                            .'<td class="text-left"><input size="15" type="text" class="form-control" name="xshippingpro[ranges]['.$counter.'][cost]" value="' . $range['cost'] . '" /></td>'
                            .'<td class="text-left"><input size="6" type="text" class="form-control" name="xshippingpro[ranges]['.$counter.'][block]" value="' . $range['block'] . '" /></td>'
                            .'<td class="text-left ocm-hide range">
                                <select class="form-control" name="xshippingpro[ranges]['.$counter.'][partial]">
                                    <option '.(($range['partial']=='0') ? 'selected': '' ) . ' value="0">' . $data['text_up'] . '</option>
                                    <option '.(($range['partial']=='2') ? 'selected': '' ) . ' value="2">' . $data['text_down'] . '</option>
                                    <option '.(($range['partial']=='1') ? 'selected': '' ) .' value="1">' . $data['text_fraction'] . '</option>
                                </select>
                            </td>'
                           .'<td class="text-left ocm-hide product">
                                <select class="form-control" name="xshippingpro[ranges]['.$counter.'][type]">
                                    <option value="">' . $data['text_na'] . '</option>
                                    <option '.(($range['type']=='quantity') ? 'selected': '' ) . ' value="quantity">' . $data['text_rate_quantity'] . '</option>
                                    <option '.(($range['type']=='weight') ? 'selected': '' ) .' value="weight">' . $data['text_rate_weight'] . '</option>
                                    <option '.(($range['type']=='price') ? 'selected': '' ) .' value="price">' . $data['text_product_price'] . '</option>
                                </select>
                            </td>'
                            .'<td class="text-right"><a class="btn btn-sm btn-danger ocm-row-remove">'.$data['text_remove'].'</a></td>'
                        .'</tr>';
        }
        if (!$method_data['ranges']) $table_body .= '<tr class="no-row"><td colspan="6">'.$data['text_no_unit_row'].'</td></tr>';

        $table_headings = array(
            array(
                'title'  => $data['text_product_name'],
                'class'  => 'ocm-hide product'
            ),
            array(
                'title'  => $data['text_start'],
                'help'   => $data['help_unit_start'],
                'class'  => 'ocm-hide range'
            ),
            array(
                'title'  => $data['text_end'],
                'help'   => $data['help_unit_end'],
                'class'  => 'ocm-hide range'
            ),
            array(
                'title' => $data['text_cost'],
                'help'  => $data['help_unit_price']
            ),
            array(
                'title' => $data['text_qnty_block'],
                'help'  => $data['help_unit_ppu']
            ),
            array(
                'title' => $data['text_partial'],
                'help'  => $data['help_partial'],
                'class'  => 'ocm-hide range'
            ),
            array(
                'title' => $data['text_product_unit_type'],
                'help'  => $data['help_product_unit_type'],
                'class'  => 'ocm-hide product'
            ),
            array(
                'title' => $data['text_action']
            )
        );

        $table_footer = '<tfoot>
                           <td colspan="7" class="text-right">&nbsp;';
        $table_footer .= $this->ocm->misc->getButton(array('type' => 'primary', 'title'=> $data['text_add_new'], 'class' => 'add-ocm-row', 'icon' => 'fa-plus-circle'));
        $table_footer .= '</tr>
                        </tfoot>';
        $return .= $this->ocm->misc->getTableSkeleton($table_headings, $table_body, $table_footer);
        $return .=  '</div>';
        return $return;
    }
    private function getSubGroups($data, $setting) {
        $prefix = $this->ocm->prefix;
        $return = '';
        for ($i=1; $i <= $data['sub_groups_count']; $i++) {
            $current_method_mode = 'no_group';
            $current_method_name =  isset($setting[$prefix . 'xshippingpro_sub_group_name'][$i]) ? $setting[$prefix . 'xshippingpro_sub_group_name'][$i]:'';

            $return .='<tr>
                <td class="text-left">Group'.$i.'</td>
                <td class="text-left">
                <select rel="'.$i.'" class="form-control sub_group xshippingpro_sub_group'.$i.'" name="' . $prefix . 'xshippingpro_sub_group['.$i.']">';

            foreach ($data['group_modes'] as $type => $name) {
                $selected = (isset($setting[$prefix . 'xshippingpro_sub_group'][$i]) && $setting[$prefix . 'xshippingpro_sub_group'][$i]==$type) ? 'selected':'';
                $current_method_mode = (isset($setting[$prefix . 'xshippingpro_sub_group'][$i]) && $setting[$prefix . 'xshippingpro_sub_group'][$i]==$type)? $type: $current_method_mode;
                $return .='<option value="'.$type.'" '.$selected.'>'.$name.'</option>';
            }

            $return .='. </select>';
            $display = ($current_method_mode != 'lowest' && $current_method_mode != 'highest') ? 'style="display:none;"' : '';
            $return .= '</td>
                        <td class="text-left"> 
                            <select '.$display.' class="form-control xshippingpro_sub_group_limit'.$i.'" name="' . $prefix . 'xshippingpro_sub_group_limit['.$i.']">';
                            for ($j=1; $j <=5; $j++) {
                                $selected=(isset($setting[$prefix . 'xshippingpro_sub_group_limit'][$j]) && $setting[$prefix . 'xshippingpro_sub_group_limit'][$j]==$j) ? 'selected':'';
                                $return .='<option value="'.$j.'" '.$selected.'>'.$j.'</option>';
                            }
                            $return .='</select>
                        </td>
                        <td class="text-left"> 
                            <input type="text" name="' . $prefix . 'xshippingpro_sub_group_name['.$i.']" value="'.$current_method_name.'" placeholder="'.$data['placeholder_group_name'].'" class="form-control" />
                        </td>
                    </tr>';
        }
        return $return;
    }
    private function isXLevelAvailable() {
        $row = $this->db->query("SELECT * from `" . DB_PREFIX . "extension` WHERE code='xlevel'")->row;
        if ($row && $this->ocm->common->getConfig('xlevel_status', 'module')) {
            return true;
        }
        return false;
    }
    private function hookRules($method_data) {
        $hook = '';
        /* HOOK RULES HERE */
        /* name must be in hook array e.g hook['name'] */
        //$hook .= $this->ocm->form->get('input', array('name' => 'hook[custom_field]', 'title' => 'Enter Custom Fields'));
        return $hook;
    }
    // local events - on save general method
    private function onSaveGeneral($save) {
        if ($save['value'][$this->ocm->prefix . 'xshippingpro_status']) {
            // save xshippingpro module
            if (VERSION >= '3.0.0.0') {
                $module_xshippingpro_status = array(
                    'module_xshippingpro_status' => true
                );
                $this->model_setting_setting->editSetting('module_xshippingpro', $module_xshippingpro_status);
            }
        }
    }
 }