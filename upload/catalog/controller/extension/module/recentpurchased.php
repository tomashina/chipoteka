<?php
class ControllerExtensionModuleRecentPurchased extends Controller {
	public function index() {
		$this->load->language('extension/module/recentpurchased');
		$data['heading_title'] = $this->language->get('heading_title');

		$this->load->model('catalog/product');
		$this->load->model('extension/recentpurchased');
		$this->load->model('tool/image');

        if ($this->customer->isLogged()) {
            $data['groupId'] = $this->customer->getGroupId();

        } else {
            $data['groupId'] ='0';
        }

		$data['text_tax']        = $this->language->get('text_tax');
		$data['button_cart']     = $this->language->get('button_cart');
		$data['button_wishlist'] = $this->language->get('button_wishlist');
		$data['button_compare']  = $this->language->get('button_compare');

		$module_recentpurchased_name =$this->config->get('module_recentpurchased_name');

        if(!empty($module_recentpurchased_name)){
             $data['module_recentpurchased_name']  = $module_recentpurchased_name;
        }else{
             $data['module_recentpurchased_name']  = $this->language->get('heading_title');
        }

		$data['recent_orders'] = array();

		$order_info = $this->model_extension_recentpurchased->getTmdOrders();

		foreach ($order_info as $result) {
			$pro_info = $this->model_catalog_product->getProduct($result['product_id']);

			    if(isset($pro_info['name'])){
					$name = $pro_info['name'];
				}else{
					$name = 0;
				}

				if(isset($pro_info['description'])){
					$description = $pro_info['description'];
				}else{
					$description = '';
				}

            $vpc = $this->currency->format($this->tax->calculate($pro_info['vpc'], $pro_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($pro_info['price'], $pro_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}


            if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                $price_2 = $this->currency->format($this->tax->calculate($pro_info['price_2'], $pro_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $price_2 = false;
            }

				if ((float)$pro_info['special']) {
					$special = $this->currency->format($this->tax->calculate($pro_info['special'], $pro_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$pro_info['special'] ? $pro_info['special'] : $pro_info['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$pro_info['rating'];
				} else {
					$rating = false;
				}

				if ($pro_info['image']) {
					$userimage = $this->model_tool_image->resize($pro_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height'));
				} else {
					$userimage = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height'));
				}

			    $data['recent_orders'][] = array(
				'product_id'    	=> $pro_info['product_id'],
				'name'       	    => $name,
				'description'       => utf8_substr(trim(strip_tags(html_entity_decode($description, ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
				'rating'            => $rating,
				'special'           => $special,
                    'vpc'       => $vpc,
				'price'             => $price,
                    'price_2'       => $price_2,
				'tax'               => $tax,
				'thumb'             => $userimage,
				'href'              => $this->url->link('product/product', 'product_id=' . $pro_info['product_id'])
	        );
		}

		/* Layout */
		if (isset($this->request->get['route'])) {
			$route = (string)$this->request->get['route'];
		} else {
			$route = 'common/home';
		}

		$this->load->model('design/layout');
		$layout_id = 0;

		if ($route == 'product/category' && isset($this->request->get['path'])) {
			$this->load->model('catalog/category');

			$path = explode('_', (string)$this->request->get['path']);

			$layout_id = $this->model_catalog_category->getCategoryLayoutId(end($path));
		}

		if ($route == 'product/product' && isset($this->request->get['product_id'])) {
			$this->load->model('catalog/product');

			$layout_id = $this->model_catalog_product->getProductLayoutId($this->request->get['product_id']);
		}

		if ($route == 'information/information' && isset($this->request->get['information_id'])) {
			$this->load->model('catalog/information');

			$layout_id = $this->model_catalog_information->getInformationLayoutId($this->request->get['information_id']);
		}

		if (!$layout_id) {
			$layout_id = $this->model_design_layout->getLayout($route);
		}

		if (!$layout_id) {
			$layout_id = $this->config->get('config_layout_id');
		}

		$this->load->model('setting/module');
		$columnrighstatus=false;
		$modules = $this->model_design_layout->getLayoutModules($layout_id, 'column_left');
		$modules1 = $this->model_design_layout->getLayoutModules($layout_id, 'column_right');
		foreach ($modules as $module) {
				$codemodule=explode('.',$module['code']);

			if($codemodule[0]=='recentpurchased')
			{
				$columnrighstatus=true;
			}
		}
		foreach ($modules1 as $module) {
			$codemodule=explode('.',$module['code']);
			if($codemodule[0]=='recentpurchased')
			{
				$columnrighstatus=true;
			}
		}
		$data['columnrighstatus']=$columnrighstatus;

		/* Layout */

		return $this->load->view('extension/module/recentpurchased', $data);

	}
}
