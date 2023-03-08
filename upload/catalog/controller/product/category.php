<?php
class ControllerProductCategory extends Controller {
	public function index() {
		$this->load->language('product/category');

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

        if ($this->customer->isLogged()) {
            $data['groupId'] = $this->customer->getGroupId();

        } else {
            $data['groupId'] ='0';
        }

        $data['shopping_cart'] = $this->url->link('checkout/cart');

		if (isset($this->request->get['filter'])) {
			$filter = $this->request->get['filter'];
		} else {
			$filter = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'p.price';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit'])) {
			$limit = (int)$this->request->get['limit'];
		} else {
			$limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		if (isset($this->request->get['path'])) {
			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$path = '';

			$parts = explode('_', (string)$this->request->get['path']);

			$category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = (int)$path_id;
				} else {
					$path .= '_' . (int)$path_id;
				}

				$category_info = $this->model_catalog_category->getCategory($path_id);

				if ($category_info) {
					$data['breadcrumbs'][] = array(
						'text' => $category_info['name'],
						'href' => $this->url->link('product/category', 'path=' . $path . $url)
					);
				}
			}
		} else {
			$category_id = 0;
		}

		$category_info = $this->model_catalog_category->getCategory($category_id);

		if ($category_info) {

			$this->document->setDescription($category_info['meta_description']);
			$this->document->setKeywords($category_info['meta_keyword']);

			$data['heading_title'] = $category_info['name'];

			$data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

			// Set the last category breadcrumb
			$data['breadcrumbs'][] = array(
				'text' => $category_info['name'],
				'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'])
			);

			if ($category_info['image']) {
				$data['thumb'] = $this->model_tool_image->resize($category_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
			} else {
				$data['thumb'] = '';
			}

			$data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');
			$data['compare'] = $this->url->link('product/compare');

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['categories'] = array();

			$results = $this->model_catalog_category->getCategories($category_id);

			foreach ($results as $result) {
				$filter_data = array(
					'filter_category_id'  => $result['category_id'],
					'filter_sub_category' => true
				);

				$data['categories'][] = array(
					'name' => $result['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
					'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '_' . $result['category_id'] . $url)
				);
			}

			$data['products'] = array();

			$filter_data = array(
				'filter_category_id' => $category_id,
                'filter_sub_category' => true,
                'filter_filter'      => $filter,
				'sort'               => $sort,
				'order'              => $order,
				'start'              => ($page - 1) * $limit,
				'limit'              => $limit
			);

			$product_total = $this->model_catalog_product->getTotalProducts($filter_data);

			$results = $this->model_catalog_product->getProducts($filter_data);

			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

                    if($this->session->data['currency']=='HRK'){
                        $priceeur = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), 'EUR');
                    }
                    else{
                        $priceeur = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), 'HRK');

                    }
				} else {
					$price = false;
                    $priceeur  ='';
				}

                $vpc = $this->currency->format($this->tax->calculate($result['vpc'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

				//price_2 agmedia

                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $price_2 = $this->currency->format($this->tax->calculate($result['price_2'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

                    if($this->session->data['currency']=='HRK'){
                        $priceeur_2 = $this->currency->format($this->tax->calculate($result['price_2'], $result['tax_class_id'], $this->config->get('config_tax')), 'EUR');
                    }
                    else{
                        $priceeur_2 = $this->currency->format($this->tax->calculate($result['price_2'], $result['tax_class_id'], $this->config->get('config_tax')), 'HRK');

                    }
                } else {
                    $price_2 = false;
                    $priceeur_2  ='';
                }

                $last_30 = null;
                if ($result['price_last_30'] != '0.0000') {
                    $last_30 = $this->currency->format($this->tax->calculate($result['price_last_30'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

                    if($this->session->data['currency']=='HRK'){
                        $lasteur_30 = $this->currency->format($this->tax->calculate($result['price_last_30'], $result['tax_class_id'], $this->config->get('config_tax')), 'EUR');

                    }
                    else{
                        $lasteur_30 = $this->currency->format($this->tax->calculate($result['price_last_30'], $result['tax_class_id'], $this->config->get('config_tax')), 'HRK');

                    }

                }else{
                    $lasteur_30  ='';
                }

				if (!is_null($result['special']) && (float)$result['special'] >= 0) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

                    if($this->session->data['currency']=='HRK'){
                        $specialeur = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')),  'EUR');
                    }
                    else{
                        $specialeur = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')),  'HRK');

                    }


					$tax_price = (float)$result['special'];
                   /* if($result['special'] >= FREESHIPPING){
                        $freeshipping = true;
                    }
                    else{
                        $freeshipping = false;
                    }*/
				} else {
					$special = false;
                    $specialeur  ='';
					$tax_price = (float)$result['price'];
                  /*  if($result['price'] >= FREESHIPPING){
                        $freeshipping = true;
                    }
                    else{
                        $freeshipping = false;
                    }*/
				}

                if ( (float)$result['special'] && ($this->config->get('salebadge_status')) ) {


                    if ($result['price_last_30'] != '0.0000' && $result['price_last_30'] < $result['price_2']) {

                        if ($this->config->get('salebadge_status') == '2') {

                            $data['sale_percent'] = number_format(((($this->tax->calculate($result['price_last_30'], $result['tax_class_id'], $this->config->get('config_tax')))-($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax'))))/(($this->tax->calculate($result['price_last_30'], $result['tax_class_id'], $this->config->get('config_tax')))/100)), 0, ',', '.');

                            //provjera postotka ako je veće od 3

                            if($data['sale_percent'] > 3){
                                $data['razlika_broj'] = ($this->tax->calculate($result['price_last_30'], $result['tax_class_id'], $this->config->get('config_tax'))) - ($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')));

                                //provjera razlike  veće od 100 kn prikaži cifru inače postotak
                                if($data['razlika_broj'] > 100){
                                    $sale_badge = $this->currency->format($this->tax->calculate($result['price_last_30'], $result['tax_class_id'], $this->config->get('config_tax')) -  $this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                                }
                                else{
                                    $data['sale_badge'] = '-' .number_format(((($this->tax->calculate($result['price_last_30'], $result['tax_class_id'], $this->config->get('config_tax')))-($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax'))))/(($this->tax->calculate($result['price_last_30'], $result['tax_class_id'], $this->config->get('config_tax')))/100)), 0, ',', '.'). '%';
                                }

                            }
                            else{

                                $sale_badge = '-' . number_format(((($this->tax->calculate($result['price_last_30'], $result['tax_class_id'], $this->config->get('config_tax')))-($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax'))))/(($this->tax->calculate($result['price_last_30'], $result['tax_class_id'], $this->config->get('config_tax')))/100)), 0, ',', '.') . '%';

                            }





                        } else {
                            $sale_badge = $this->language->get('basel_text_sale');
                        }

                    }

                    else{


                        if ($this->config->get('salebadge_status') == '2') {


                            $data['sale_percent'] = number_format(((($this->tax->calculate($result['price_2'], $result['tax_class_id'], $this->config->get('config_tax')))-($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax'))))/(($this->tax->calculate($result['price_2'], $result['tax_class_id'], $this->config->get('config_tax')))/100)), 0, ',', '.');

                            //provjera postotka ako je veće od 3

                            if($data['sale_percent'] > 3){
                                $data['razlika_broj'] = ($this->tax->calculate($result['price_2'], $result['tax_class_id'], $this->config->get('config_tax'))) - ($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')));

                                //provjera razlike  veće od 100 kn prikaži cifru inače postotak
                                if($data['razlika_broj'] > 100){
                                    $data['sale_badge'] = $this->currency->format($this->tax->calculate($result['price_2'], $result['tax_class_id'], $this->config->get('config_tax')) -  $this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                                }
                                else{
                                    $sale_badge = '-' .number_format(((($this->tax->calculate($result['price_2'], $result['tax_class_id'], $this->config->get('config_tax')))-($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax'))))/(($this->tax->calculate($result['price_2'], $result['tax_class_id'], $this->config->get('config_tax')))/100)), 0, ',', '.'). '%';
                                }

                            }
                            else{

                                $sale_badge = '-' . number_format(((($this->tax->calculate($result['price_2'], $result['tax_class_id'], $this->config->get('config_tax')))-($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax'))))/(($this->tax->calculate($result['price_2'], $result['tax_class_id'], $this->config->get('config_tax')))/100)), 0, ',', '.') . '%';

                            }


                        } else {
                            $sale_badge = $this->language->get('basel_text_sale');
                        }

                    }

                } else {
                    $sale_badge = false;
                    $data['sale_percent']='';
                }
	
				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format($tax_price, $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}

                $rokisporuke = $result['isbn'];

                $saljemodo = date('d.m.Y', mktime(0, 0, 0, date('m'), date('d') + $rokisporuke, date('Y')));

                $pj = $this->model_catalog_product->getWebActions($result['product_id']);

                if($pj=='1'){

                    $pj ='WEB';
                }
                else{

                    $pj ='';
                }

				$data['products'][] = array(
                    'product_id'   => $result['product_id'],
                    'thumb'        => $image,
                    'name'         => $result['name'],
                    'price'        => $price,
                    'priceeur'       => $priceeur,
                    'specialeur'     => $specialeur,
                    'priceeur_2'       => $priceeur_2,
                    'lasteur_30'      => $lasteur_30,
                    'price_2'      => $price_2,
                    'last_30'      => $last_30,
                    'sale_badge' => $sale_badge,
                    'sale_percent' => $data['sale_percent'],
                    'quantity'  => $result['quantity'],
                    'pakiranje' => $result['pakiranje'] ?: 1,
                    'vpc'          => $vpc,
                    'pj'           => $pj,
                    'special'      => $special,
                  /*  'freeshipping' => $freeshipping,*/
                    'categoryname' => ' - '.$category_info['name'],
                    'tax'          => $tax,
                    'minimum'      => $result['minimum'] > 0 ? $result['minimum'] : 1,
                    'rating'       => $result['rating'],
					'href'         => $this->url->link('product/product', 'path=' . $this->request->get['path'] . '&product_id=' . $result['product_id'] . $url)
				);
			}

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['sorts'] = array();

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_default'),
				'value' => 'p.sort_order-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.sort_order&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=pd.name&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=pd.name&order=DESC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=DESC' . $url)
			);

			if ($this->config->get('config_review_status')) {
				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=rating&order=DESC' . $url)
				);

				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_asc'),
					'value' => 'rating-ASC',
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=rating&order=ASC' . $url)
				);
			}

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.model&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.model&order=DESC' . $url)
			);

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			$data['limits'] = array();

			$limits = array_unique(array($this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'), 25, 50, 75, 100));

			sort($limits);

			foreach($limits as $value) {
				$data['limits'][] = array(
					'text'  => $value,
					'value' => $value,
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url . '&limit=' . $value)
				);
			}

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}


            $this->document->addOGMeta('property="og:url"', $this->url->link('product/category', 'path=' . $category_info['category_id'] . ( ($page != 1) ? '&page='. $page : '' ), true) );

			$pagination = new Pagination();
			$pagination->total = $product_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url . '&page={page}');

			$data['pagination'] = $pagination->render();

			if($category_info['meta_title'] != ''){

			    $metanaslov = $category_info['meta_title'];

            }
			else{
                $metanaslov = $category_info['name'];

            }



            if ($page == 1) {
            $this->document->setTitle($metanaslov );

            } else {
                $this->document->setTitle($metanaslov. ' - Strana: ' . $page );
            }

			//$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

            $data['results'] = $product_total;

			// http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
			if ($page == 1) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id']), 'canonical');
			} else {
				$this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . '&page='. $page), 'canonical');
			}
			
			if ($page > 1) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . (($page - 2) ? '&page='. ($page - 1) : '')), 'prev');
			}

			if ($limit && ceil($product_total / $limit) > $page) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . '&page='. ($page + 1)), 'next');
			}

			$data['sort'] = $sort;
			$data['order'] = $order;
			$data['limit'] = $limit;

			$data['continue'] = $this->url->link('common/home');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('product/category', $data));
		} else {
			$url = '';

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('product/category', $url)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}
}
