<?php
class ControllerInformationFaq extends Controller {
	public function index() {
		$this->load->language('information/faq');
		$this->load->model('catalog/faq');

		$canonical_url = $this->url->link('information/faq', '', true);
		$heading_title = $this->language->get('heading_title');
		$meta_description = $this->language->get('meta_description');

		$this->document->setTitle($heading_title);
		$this->document->setDescription($meta_description);
		$this->document->addLink($canonical_url, 'canonical');
		$this->document->addOGMeta('property="og:url"', $canonical_url);

		$data['breadcrumbs'] = array(
			array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			),
			array(
				'text' => $heading_title,
				'href' => $canonical_url
			)
		);

		$data['heading_title'] = $heading_title;
		$data['text_intro'] = $this->language->get('text_intro');
		$data['text_categories'] = $this->language->get('text_categories');
		$data['text_empty'] = $this->language->get('text_empty');
		$faq_anchor_parts = parse_url(html_entity_decode($this->url->link('information/faq'), ENT_QUOTES, 'UTF-8'));
		$data['faq_anchor_url'] = $faq_anchor_parts['path'] ?? '/';

		if (!empty($faq_anchor_parts['query'])) {
			$data['faq_anchor_url'] .= '?' . $faq_anchor_parts['query'];
		}

		$data['categories'] = $this->model_catalog_faq->getFaqCategoriesWithItems();

		$faq_items = array();

		foreach ($data['categories'] as $category) {
			foreach ($category['items'] as $item) {
				$faq_items[] = $item;
			}
		}

		$this->load->model('catalog/structured_data');
		$data['faq_json_ld'] = $this->model_catalog_structured_data->buildFaqPage(
			$heading_title,
			$meta_description,
			$data['breadcrumbs'],
			$canonical_url,
			$faq_items
		);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('information/faq', $data));
	}
}
