<?php
class ControllerInformationInformation extends Controller {
	public function index() {
		$this->load->language('information/information');

		$this->load->model('catalog/information');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		if (isset($this->request->get['information_id'])) {
			$information_id = (int)$this->request->get['information_id'];
		} else {
			$information_id = 0;
		}

		$information_info = $this->model_catalog_information->getInformation($information_id);

		if ($information_info) {
			$canonical_url = $this->url->link('information/information', 'information_id=' .  $information_id, true);
			$this->document->setTitle($information_info['meta_title']);
			$this->document->setDescription($information_info['meta_description']);
			$this->document->setKeywords($information_info['meta_keyword']);

            $this->document->addOGMeta('property="og:url"', $canonical_url );

            $this->document->addLink($canonical_url, 'canonical');

			$data['breadcrumbs'][] = array(
				'text' => $information_info['title'],
				'href' => $canonical_url
			);

			$data['heading_title'] = $information_info['title'];
            /* Parent Work Starts */
            $this->load->model('tool/image');
            if ($information_info['image']) {
                $data['thumb'] = $this->model_tool_image->resize($information_info['image'], 228, 228);
            } else {
                $data['thumb'] = '';
            }
            /* Parent Work Ends */

			$data['description'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');
			$data['policy_faq_heading'] = $this->language->get('text_policy_faq_heading');
			$data['policy_faq_items'] = $this->getPolicyFaqItems($information_id);

			$this->load->model('catalog/structured_data');
			$data['policy_json_ld'] = $this->model_catalog_structured_data->buildInformationPolicyPage(
				$information_info,
				$information_id,
				$data['breadcrumbs'],
				$canonical_url,
				$data['policy_faq_items']
			);

			$data['continue'] = $this->url->link('common/home');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('information/information', $data));
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('information/information', 'information_id=' . $information_id)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['heading_title'] = $this->language->get('text_error');

			$data['text_error'] = $this->language->get('text_error');

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

	private function getPolicyFaqItems(int $information_id): array {
		if ($information_id === 3) {
			return array(
				array(
					'question' => $this->language->get('faq_purchase_payment_question'),
					'answer' => $this->language->get('faq_purchase_payment_answer')
				),
				array(
					'question' => $this->language->get('faq_purchase_delivery_question'),
					'answer' => $this->language->get('faq_purchase_delivery_answer')
				),
				array(
					'question' => $this->language->get('faq_purchase_return_question'),
					'answer' => $this->language->get('faq_purchase_return_answer')
				),
				array(
					'question' => $this->language->get('faq_purchase_warranty_question'),
					'answer' => $this->language->get('faq_purchase_warranty_answer')
				),
				array(
					'question' => $this->language->get('faq_purchase_service_question'),
					'answer' => $this->language->get('faq_purchase_service_answer')
				)
			);
		}

		if ($information_id === 5) {
			return array(
				array(
					'question' => $this->language->get('faq_terms_scope_question'),
					'answer' => $this->language->get('faq_terms_scope_answer')
				),
				array(
					'question' => $this->language->get('faq_terms_account_question'),
					'answer' => $this->language->get('faq_terms_account_answer')
				)
			);
		}

		if ($information_id === 6) {
			return array(
				array(
					'question' => $this->language->get('faq_privacy_data_question'),
					'answer' => $this->language->get('faq_privacy_data_answer')
				),
				array(
					'question' => $this->language->get('faq_privacy_rights_question'),
					'answer' => $this->language->get('faq_privacy_rights_answer')
				)
			);
		}

		return array();
	}

	public function agree() {
		$this->load->model('catalog/information');

		if (isset($this->request->get['information_id'])) {
			$information_id = (int)$this->request->get['information_id'];
		} else {
			$information_id = 0;
		}

		$output = '';

		$information_info = $this->model_catalog_information->getInformation($information_id);

		if ($information_info) {
			$output .= html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8') . "\n";
		}

		$this->response->addHeader('X-Robots-Tag: noindex');

		$this->response->setOutput($output);
	}
}
