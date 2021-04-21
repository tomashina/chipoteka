<?php 
class ControllerExtensionBlogBlog extends Controller {
	
	private $error = array();
	
	public function index() { 
	 
		$this->language->load('blog/blog');
		
		$this->load->model('extension/blog/blog');

		$this->load->model('tool/image');
		
		$data['breadcrumbs'] = array();

      	$data['breadcrumbs'][] = array(
        	'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home')
      	);

      	$data['breadcrumbs'][] = array(
        	'text'      => $this->language->get('text_blog'),
			'href'      => $this->url->link('extension/blog/home')
      	);
				
		$this->load->model('extension/blog/blog_category');
		
		if ($this->language->get('direction') == 'rtl') { $data['tooltip_align'] = 'right'; } else { $data['tooltip_align'] = 'left'; }
		
		if (isset($this->request->get['blogpath'])) {
			$path = '';

			$parts = explode('_', (string)$this->request->get['blogpath']);

			$blog_category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = $path_id;
				} else {
					$path .= '_' . $path_id;
				}

				$category_info = $this->model_extension_blog_blog_category->getBlogCategory($path_id);

				if ($category_info) {
					$data['breadcrumbs'][] = array(
						'text' => $category_info['name'],
						'href' => $this->url->link('extension/blog/category', 'blogpath=' . $path)
					);
				}
			}

			// Set the last category breadcrumb
			$category_info = $this->model_extension_blog_blog_category->getBlogCategory($blog_category_id);

			if ($category_info) {
				$url = '';

				$data['breadcrumbs'][] = array(
					'text' => $category_info['name'],
					'href' => $this->url->link('extension/blog/category', 'blogpath=' . $this->request->get['blogpath'] . $url)
				);
			}
		}		
		
		if (isset($this->request->get['blog_id'])) {
			$blog_id = $this->request->get['blog_id'];
		} else {
			$blog_id = 0;
		}

		$blog_info = $this->model_extension_blog_blog->getBlog($blog_id);
   		
		if ($blog_info) {
			$url = '';
			
			if (isset($this->request->get['blogpath'])) {
				$url .= '&blogpath=' . $this->request->get['blogpath'];
			}
			
			$data['breadcrumbs'][] = array(
			'text'      => $blog_info['title'],
			'href' => $this->url->link('extension/blog/blog', $url . '&blog_id=' . $this->request->get['blog_id'])
			);
			
			$data['new_read_counter_value'] = $blog_info['count_read']+1;
			$this->model_extension_blog_blog->updateBlogReadCounter($this->request->get['blog_id'], $data['new_read_counter_value']);
			$data['comment_total'] = $this->model_extension_blog_blog->getTotalCommentsByBlogId($this->request->get['blog_id']);

			if (isset($this->request->get['blog_id'])) {
				
			$data['post_date_added_status'] = $this->config->get('blogsetting_post_date_added');
			$data['post_comments_count_status'] = $this->config->get('blogsetting_post_comments_count');
			$data['post_page_view_status'] = $this->config->get('blogsetting_post_page_view');
			$data['post_author_status'] = $this->config->get('blogsetting_post_author');
			$data['share_status'] = $this->config->get('blogsetting_share');
			$data['blogsetting_post_thumb'] = $this->config->get('blogsetting_post_thumb');
			$data['date_added_status'] = $this->config->get('blogsetting_date_added');
			$data['comments_count_status'] = $this->config->get('blogsetting_comments_count');
			$data['page_view_status'] = $this->config->get('blogsetting_page_view');
			$data['author_status'] = $this->config->get('blogsetting_author');
			$data['rel_thumb_status'] = $this->config->get('blogsetting_rel_thumb');
			$data['rel_per_row'] = $this->config->get('blogsetting_rel_blog_per_row');
			$data['rel_prod_per_row'] = $this->config->get('blogsetting_rel_prod_per_row');
			$rel_img_width = $this->config->get('blogsetting_rel_thumbs_w');
			$rel_img_height = $this->config->get('blogsetting_rel_thumbs_h');
			$rel_prod_img_height = $this->config->get('blogsetting_rel_prod_height');
			$rel_prod_img_width = $this->config->get('blogsetting_rel_prod_width');

			// Related posts
			$data['related_blogs'] = array();
			
			$related_blogs = $this->model_extension_blog_blog->getRelatedBlog($this->request->get['blog_id']);
		
			foreach ($related_blogs as $result) {
			
			if ($result['tags']) {
				$tags = explode(',', $result['tags']);
			} else {
				$tags = false;
			}
			
      			$data['related_blogs'][] = array(
        		'title' => $result['title'],
				'count_read' => $result['count_read'],
				'short_description' => utf8_substr(strip_tags(html_entity_decode($result['short_description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('blogsetting_rel_characters')) . '..',
				'tags' 	=> $tags,
				'author' => $result['author'],
				'comment_total' => $this->model_extension_blog_blog->getTotalCommentsByBlogId($result['blog_id']),
        		'date_added_full' => $result['date_added'],
        		'image' => $this->model_tool_image->resize($result['image'], $rel_img_width, $rel_img_height),
	    		'href'  => $this->url->link('extension/blog/blog', 'blog_id=' . $result['blog_id'])
      			);
    		  }
    		}
			
			if ($blog_info['page_title']) {
			$this->document->setTitle($blog_info['page_title']);
			} else {
			$this->document->setTitle($blog_info['title']);
			}
			
			$this->document->setDescription($blog_info['meta_description']);
			$this->document->setKeywords($blog_info['meta_keyword']);
			
			$this->document->addLink($this->url->link('extension/blog/blog', 'blog_id=' . $this->request->get['blog_id']), 'canonical');
										
      		$data['heading_title'] = $blog_info['title'];
			
			$data['description'] = html_entity_decode($blog_info['description'], ENT_QUOTES, 'UTF-8');
			
			$data['short_description'] = html_entity_decode($blog_info['short_description'], ENT_QUOTES, 'UTF-8');
			
			$data['img_width'] = $this->config->get('blogsetting_post_thumbs_w');
			if (empty($data['img_width'])) {
			$data['img_width'] = 1140;
			}
			
			$data['img_height'] = $this->config->get('blogsetting_post_thumbs_h');
			if (empty($data['img_height'])) {
			$data['img_height'] = 700;
			}
	      	
			if ($blog_info['image']) {
			$data['main_thumb'] = $this->model_tool_image->resize($blog_info['image'], $data['img_width'], $data['img_height']);
			$this->document->addLink($data['main_thumb'], 'image');
			} else {
			$data['main_thumb'] = false;
			}
			
			$data['tags'] = array();

			if ($blog_info['tags']) {
				$tags = explode(',', $blog_info['tags']);

				foreach ($tags as $tag) {
					$data['tags'][] = array(
						'tag'  => trim($tag),
						'href' => $this->url->link('extension/blog/home', 'tag=' . trim($tag))
					);
				}
			}
			
			// Related products
			
			$this->load->model('extension/basel/basel');
			$this->load->language('basel/basel_theme');
			$data['basel_button_quickview'] = $this->language->get('basel_button_quickview');
			$data['basel_text_sale'] = $this->language->get('basel_text_sale');
			$data['basel_text_new'] = $this->language->get('basel_text_new');
			$data['basel_text_days'] = $this->language->get('basel_text_days');
			$data['basel_text_hours'] = $this->language->get('basel_text_hours');
			$data['basel_text_mins'] = $this->language->get('basel_text_mins');
			$data['basel_text_secs'] = $this->language->get('basel_text_secs');
			$data['basel_list_style'] = $this->config->get('basel_list_style');
			$data['salebadge_status'] = $this->config->get('salebadge_status');
			$data['stock_badge_status'] = $this->config->get('stock_badge_status');
			$data['basel_text_out_of_stock'] = $this->language->get('basel_text_out_of_stock');
			$data['default_button_cart'] = $this->language->get('button_cart');
			$data['countdown_status'] = $this->config->get('countdown_status');
		
			$data['products'] = array();
			
			$results = $this->model_extension_blog_blog->getProductRelated($this->request->get['blog_id']);

			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $rel_prod_img_width, $rel_prod_img_height);
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $rel_prod_img_width, $rel_prod_img_height);
				}
				
				$image2 = $this->model_catalog_product->getProductImages($result['product_id']);
				if(isset($image2[0]['image']) && !empty($image2[0]['image'])){
					$image2 =$image2[0]['image'];
				} else {
					$image2 = false;
				}
				if ((float)$result['special']) {
					$date_end = $this->model_extension_basel_basel->getSpecialEndDate($result['product_id']);
				} else {
					$date_end = false;
				}

				if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}
				if ( (float)$result['special'] && ($this->config->get('salebadge_status')) ) {
					if ($this->config->get('salebadge_status') == '2') {
						$sale_badge = '-' . number_format(((($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')))-($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax'))))/(($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')))/100)), 0, ',', '.') . '%';
					} else {
						$sale_badge = $this->language->get('basel_text_sale');
					}		
				} else {
					$sale_badge = false;
				}
				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}
				if (strtotime($result['date_available']) > strtotime('-' . $this->config->get('newlabel_status') . ' day')) {
					$is_new = true;
				} else {
					$is_new = false;
				}

				$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'quantity'  => $result['quantity'],
					'thumb'       => $image,
					'thumb2'  => $this->model_tool_image->resize($image2, $this->config->get('theme_default_image_product_width'), $this->config->get('theme_default_image_product_height')),
					'sale_end_date' => $date_end['date_end'] ?? '',
					'name'        => $result['name'],
					'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('config_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'sale_badge' => $sale_badge,
					'new_label'  => $is_new,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $rating,
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
				);
			}
			
			$data['store'] = $this->config->get('config_name');
			
			if ($this->request->server['HTTPS']) {
				$server = $this->config->get('config_ssl');
			} else {
				$server = $this->config->get('config_url');
			}
			
			if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
				$data['logo'] = $server . 'image/' . $this->config->get('config_logo');
			} else {
				$data['logo'] = '';
			}
			
			$data['text_tags'] = $this->language->get('text_tags');
			$data['text_posted_on'] = $this->language->get('text_posted_on');
			$data['text_posted_by'] = $this->language->get('text_posted_by');
			$data['text_read'] = $this->language->get('text_read');
			$data['button_cart'] = $this->language->get('button_cart');
			$data['button_wishlist'] = $this->language->get('button_wishlist');
			$data['button_compare'] = $this->language->get('button_compare');
			$data['text_tax'] = $this->language->get('text_tax');
      		$data['text_related_blog'] = $this->language->get('text_related_blog');
			$data['text_related_products'] = $this->language->get('text_related_products');
      		$data['text_read_more'] = $this->language->get('text_read_more');
      		$data['text_write_comment'] = $this->language->get('text_write_comment');
      		$data['entry_name'] = $this->language->get('entry_name');
      		$data['entry_email'] = $this->language->get('entry_email');
			$data['entry_comment'] = $this->language->get('entry_comment');
      		$data['text_comments'] = $this->language->get('text_comments');
			$data['text_write_comment'] = $this->language->get('text_write_comment');
      		$data['entry_captcha'] = $this->language->get('entry_captcha');
      		$data['button_send'] = $this->language->get('button_send');

			$data['date_added_day'] = date("d",strtotime($blog_info['date_added']));
			$m = date("m",strtotime($blog_info['date_added']));
			$months = array (
					1 => $this->language->get('text_month_jan'),
					2 => $this->language->get('text_month_feb'),
					3 => $this->language->get('text_month_mar'),
					4 => $this->language->get('text_month_apr'),
					5 => $this->language->get('text_month_may'),
					6 => $this->language->get('text_month_jun'),
					7 => $this->language->get('text_month_jul'),
					8 => $this->language->get('text_month_aug'),
					9 => $this->language->get('text_month_sep'),
					10 => $this->language->get('text_month_oct'),
					11 => $this->language->get('text_month_nov'),
					12 => $this->language->get('text_month_dec')
					);
			$data['date_added_month'] = $months[(int)$m];
			
			$data['author'] = $blog_info['author'];
			
			$data['allow_comment'] = $blog_info['allow_comment'];
			
			$data['continue'] = $this->url->link('common/home');
			
			$data['blog_id'] = (int)$this->request->get['blog_id'];
					
	  		$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
			
			$this->response->setOutput($this->load->view('blog/blog', $data));
			
    	} else {
			
			$url = '';
			
      		$data['breadcrumbs'] [] = array(
        		'href'      => $this->url->link('extension/blog/blog', $url . '&blog_id=' . $this->request->get['blog_id']),
        		'text'      => $this->language->get('text_error')
      		);
				
	  		$this->document->setTitle($this->language->get('text_error'));

			$data['heading_title'] = $this->language->get('text_error');

			$data['text_error'] = $this->language->get('text_error');

			$data['button_continue'] = $this->language->get('button_continue');

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
	
		
		public function comment() {
			
		$this->load->language('blog/blog');

		$this->load->model('extension/blog/blog');
		
		$data['text_comments'] = $this->language->get('text_comments');

		if (isset($this->request->get['page'])) {
				$page = $this->request->get['page'];
			} else {
				$page = 1;
			}

		$data['comments'] = array();

		$comment_total = $this->model_extension_blog_blog->getTotalCommentsByBlogId($this->request->get['blog_id']);
		
		$limit = $this->config->get('blogsetting_comment_per_page');
		if (empty($limit)) {
		$limit = 5;
		}
			
		$results = $this->model_extension_blog_blog->getCommentsByBlogId($this->request->get['blog_id'], ($page - 1) * $limit, $limit);

		foreach ($results as $result) {
        		$data['comments'][] = array(
        			'name'     => $result['name'],
					'email'     => $result['email'],
					'comment'       => strip_tags($result['comment']),
        			'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
        		);
      		}	
		
        $pagination = new Pagination();
		$pagination->total = $comment_total;
		$pagination->page = $page;
				
		$pagination->limit = $this->config->get('blogsetting_comment_per_page');
		if (empty($pagination->limit)) {
		$pagination->limit = 5;
		}
		
		$pagination->url = $this->url->link('extension/blog/blog/comment', 'blog_id=' . $this->request->get['blog_id'] . '&page={page}');
		
		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($comment_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($comment_total - $limit)) ? $comment_total : ((($page - 1) * $limit) + $limit), $comment_total, ceil($comment_total / $limit));
		
		$this->response->setOutput($this->load->view('blog/comment', $data));
		
	}


		public function write() {
		$this->load->language('blog/blog');
		
		$data['entry_comment'] = $this->language->get('entry_comment');
		
		$json = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			
			if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 100)) {
				$json['error'] = $this->language->get('error_name');
			}
			
			if ((utf8_strlen($this->request->post['email']) < 1) || (utf8_strlen($this->request->post['email']) > 100)) {
				$json['error'] = $this->language->get('error_email');
			}

			if ((utf8_strlen($this->request->post['comment']) < 5) || (utf8_strlen($this->request->post['comment']) > 3000)) {
				$json['error'] = $this->language->get('error_comment');
			}
			
			if (empty($this->session->data['captcha_comment']) || ($this->session->data['captcha_comment'] != $this->request->post['captcha_comment'])) {
				$json['error'] = $this->language->get('error_captcha');
			}

			
			if (!isset($json['error'])) {
				$this->load->model('extension/blog/blog');

				$this->model_extension_blog_blog->addComment($this->request->get['blog_id'], $this->request->post);
				
				if($this->config->get('blogsetting_comment_approve')){
				$json['success'] = $this->language->get('text_success_approve');
				}else{
				$json['success'] = $this->language->get('text_success');
				}
				
				if($this->config->get('blogsetting_comment_notification')){

				$mail = new Mail();
				$mail->protocol = $this->config->get('config_mail_protocol');
				$mail->parameter = $this->config->get('config_mail_parameter');
				if ((float)VERSION >= 3.0) {
					$mail->smtp_hostname = $this->config->get('config_mail_smtp_host');
				} else {
					$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
				}
				$mail->smtp_username = $this->config->get('config_mail_smtp_username');
				$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
				$mail->smtp_port = $this->config->get('config_mail_smtp_port');
				$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
				$mail->setTo($this->config->get('config_email'));
				$mail->setFrom($this->config->get('config_email'));
				$mail->setSender($this->request->post['name']);
				$mail->setSubject(sprintf($this->language->get('email_notification'), $this->request->post['name']));
				$mail->setText(strip_tags($this->request->post['comment']));
				$mail->send();
					
			}	
		  }
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	
	public function captcha() {
		$num1=rand(2,6); //Generate First number between 2 and 6 
		$num2=rand(2,6); //Generate Second number between 1 and 9
		$this->session->data['captcha_comment'] = $num1+$num2;
		$image = imagecreatetruecolor(58, 22);
		$width = imagesx($image);
		$height = imagesy($image);
		$black = imagecolorallocate($image, 50, 50, 50);
		$white = imagecolorallocate($image, 255, 255, 255);
		imagefilledrectangle($image, 0, 0, $width, $height, $white);
		imagestring($image, 4, 0, 3, "$num1"." + "."$num2"." =", $black);
		header('Content-type: image/png');
		imagepng($image);
		imagedestroy($image);
	}
	
	
}