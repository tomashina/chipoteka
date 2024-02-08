<?php
class ControllerCommonHeader extends Controller {
	public function index() {
		// Analytics
		$this->load->model('setting/extension');

		$data['analytics'] = array();

        if ($this->customer->isLogged()) {
            $data['groupId'] = $this->customer->getGroupId();

        } else {
            $data['groupId'] ='0';
        }

		$analytics = $this->model_setting_extension->getExtensions('analytics');

		foreach ($analytics as $analytic) {
			if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
				$data['analytics'][] = $this->load->controller('extension/analytics/' . $analytic['code'], $this->config->get('analytics_' . $analytic['code'] . '_status'));
			}
		}

		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
		}

		//\Agmedia\Helpers\Log::store($this->request->get, 'req');

		// fj.agmedia.hr
        $info_id = 0;
        if (isset($this->request->get['information_id'])) {
            $info_id = $this->request->get['information_id'];
        }

        $this->load->model('extension/information_parent');

        $data['informations'] = array();

        foreach ($this->model_extension_information_parent->getInformations() as $result) {
            $data['informations_children'] = array();
            $parent_id = 0;

            foreach ($this->model_extension_information_parent->getInformations($result['information_id']) as $child_result) {
                if ($child_result['information_id'] == $info_id) {
                    $parent_id = $result['information_id'];
                }

                $data['informations_children'][] = array(
                    'title' => $child_result['title'],
                    'group_id' => $child_result['group_id'],
                    'active' => ($child_result['information_id'] == $info_id) ? 1 : 0,
                    'href'  => $this->url->link('information/information', 'information_id=' . $child_result['information_id'])
                );
            }

            $data['informations'][] = array(
                'title' => $result['title'],
                'infoid' => $result['information_id'],
                'active' => ($parent_id && $parent_id == $result['information_id']) ? 1 : 0,
                'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id']),
                'informations_children'  => $data['informations_children'],
            );
        }

		$data['title'] = $this->document->getTitle();

		$data['base'] = $server;
		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();
		$data['links'] = $this->document->getLinks();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts('header');
		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');

		$data['name'] = $this->config->get('config_name');

		if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = $server . 'image/' . $this->config->get('config_logo');
		} else {
			$data['logo'] = '';
		}

		$this->load->language('common/header');

		// Wishlist
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');

			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), $this->model_account_wishlist->getTotalWishlist());
		} else {
			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0));
		}

		$data['text_logged'] = $this->customer->getFirstName().' '.$this->customer->getLastName();
		
		$data['home'] = $this->url->link('common/home');
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['logged'] = $this->customer->isLogged();
		$data['account'] = $this->url->link('account/account', '', true);
        $data['edit'] = $this->url->link('account/edit', '', true);
		$data['register'] = $this->url->link('account/register', '', true);
		$data['login'] = $this->url->link('account/login', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['transaction'] = $this->url->link('account/transaction', '', true);
		$data['download'] = $this->url->link('account/download', '', true);
		$data['logout'] = $this->url->link('account/logout', '', true);
		$data['shopping_cart'] = $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);
		$data['contact'] = $this->url->link('information/contact');
		$data['telephone'] = $this->config->get('config_telephone');


        // For page specific og tags
        if (isset($this->request->get['route'])) {
            if (isset($this->request->get['product_id'])) {
                $class = '-' . $this->request->get['product_id'];
                $this->document->addOGMeta('property="og:type"', 'product');
            } elseif (isset($this->request->get['path'])) {
                $class = '-' . $this->request->get['path'];
            } elseif (isset($this->request->get['manufacturer_id'])) {
                $class = '-' . $this->request->get['manufacturer_id'];
            } elseif (isset($this->request->get['information_id'])) {
                $class = '-' . $this->request->get['information_id'];
                $this->document->addOGMeta('property="og:type"', 'article');
            } else {
                $class = '';
            }
            $data['class'] = str_replace('/', '-', $this->request->get['route']) . $class;
        } else {
            $data['class'] = 'common-home';
            $this->document->addOGMeta('property="og:type"', 'website');
        }
        $this->load->model('tool/image');
        $data['logo_meta'] = 'https://www.chipoteka.hr/catalog/view/theme/chipoteka/honeog.jpg';
        $data['ogmeta'] = $this->document->getOGMeta();
		
		$data['language'] = $this->load->controller('common/language');
		$data['currency'] = $this->load->controller('common/currency');
		$data['search'] = $this->load->controller('common/search');
		$data['cart'] = $this->load->controller('common/cart');
		$data['menu'] = $this->load->controller('common/menu');

		return $this->load->view('common/header', $data);
	}
}
