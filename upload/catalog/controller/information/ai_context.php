<?php
class ControllerInformationAiContext extends Controller {
	public function index() {
		$this->load->language('information/ai_context');

		$base_url = $this->getStoreBaseUrl();

		$this->document->setTitle($this->language->get('meta_title'));
		$this->document->setDescription($this->language->get('meta_description'));
		$this->document->addLink($base_url . '/ai-context', 'canonical');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $base_url . '/ai-context'
		);

		$data['heading_title'] = $this->language->get('heading_title');
		$data['intro'] = $this->language->get('text_intro');
		$data['llms_url'] = $base_url . '/llms.txt';
		$data['sections'] = $this->buildHtmlSections($base_url);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('information/ai_context', $data));
	}

	public function llms() {
		$this->response->addHeader('Content-Type: text/plain; charset=utf-8');
		$this->response->addHeader('Cache-Control: public, max-age=3600');
		$this->response->addHeader('X-Content-Type-Options: nosniff');
		$this->response->addHeader('Content-Disposition: inline; filename="llms.txt"');
		$this->response->setOutput($this->buildLlmsText($this->getStoreBaseUrl()));
	}

	private function buildHtmlSections(string $base_url): array {
		return array(
			array(
				'title' => $this->language->get('section_machine_title'),
				'items' => array(
					array(
						'label' => 'llms.txt',
						'url' => $base_url . '/llms.txt',
						'description' => $this->language->get('resource_llms')
					),
					array(
						'label' => 'Sitemap index',
						'url' => $base_url . '/sitemap-index.xml',
						'description' => $this->language->get('resource_sitemap')
					),
					array(
						'label' => 'Robots policy',
						'url' => $base_url . '/robots.txt',
						'description' => $this->language->get('resource_robots')
					),
					array(
						'label' => 'OpenAI product discovery feed',
						'url' => $base_url . '/openai-products.jsonl',
						'description' => $this->language->get('resource_openai_feed')
					)
				)
			),
			array(
				'title' => $this->language->get('section_shopping_title'),
				'items' => array(
					array(
						'label' => $this->language->get('link_home'),
						'url' => $base_url . '/',
						'description' => $this->language->get('resource_home')
					),
					array(
						'label' => $this->language->get('link_about'),
						'url' => $base_url . '/o-chipoteci',
						'description' => $this->language->get('resource_about')
					),
					array(
						'label' => $this->language->get('link_brands'),
						'url' => $base_url . '/brandovi',
						'description' => $this->language->get('resource_brands')
					),
					array(
						'label' => $this->language->get('link_stores'),
						'url' => $base_url . '/poslovnice',
						'description' => $this->language->get('resource_stores')
					),
					array(
						'label' => $this->language->get('link_service'),
						'url' => $base_url . '/servis',
						'description' => $this->language->get('resource_service')
					),
					array(
						'label' => $this->language->get('link_wholesale'),
						'url' => $base_url . '/veleprodaja',
						'description' => $this->language->get('resource_wholesale')
					)
				)
			),
			array(
				'title' => $this->language->get('section_policy_title'),
				'items' => array(
					array(
						'label' => $this->language->get('link_purchase_terms'),
						'url' => $base_url . '/uvjeti-kupovine',
						'description' => $this->language->get('resource_purchase_terms')
					),
					array(
						'label' => $this->language->get('link_terms'),
						'url' => $base_url . '/opci-uvjeti-koristenja',
						'description' => $this->language->get('resource_terms')
					),
					array(
						'label' => $this->language->get('link_privacy'),
						'url' => $base_url . '/zastita-privatnosti-i-podataka',
						'description' => $this->language->get('resource_privacy')
					)
				)
			)
		);
	}

	private function buildLlmsText(string $base_url): string {
		$lines = array(
			'# Chipoteka',
			'',
			'> Chipoteka is a Croatian electronics, IT, tools, appliances and consumer technology retailer. The site contains product pages, category and brand listings, store and service information, policies, XML sitemaps and machine-readable product feeds for AI/product discovery.',
			'',
			'Important notes:',
			'- Primary site language: Croatian.',
			'- Store country: HR; currency: EUR.',
			'- Product prices, availability, shipping and return information can change and should be verified on the linked product pages or product feed.',
			'- Product discovery feeds are intended for search and product discovery; checkout happens on www.chipoteka.hr.',
			'- Respect robots.txt, sitemap signals and crawler/WAF policies.',
			'',
			'## Core Resources',
			'- [Home](' . $base_url . '/): Main ecommerce site.',
			'- [AI context page](' . $base_url . '/ai-context): Human-readable context page for AI systems and auditors.',
			'- [Robots policy](' . $base_url . '/robots.txt): Crawler policy and sitemap reference.',
			'- [Sitemap index](' . $base_url . '/sitemap-index.xml): XML sitemap index for product, category and content discovery.',
			'- [OpenAI product discovery feed](' . $base_url . '/openai-products.jsonl): JSONL product discovery feed with product URLs, prices, availability, identifiers and policy links.',
			'',
			'## Shopping Context',
			'- [About Chipoteka](' . $base_url . '/o-chipoteci): Basic company and store context.',
			'- [Brands](' . $base_url . '/brandovi): Manufacturer and brand listing.',
			'- [Stores](' . $base_url . '/poslovnice): Store locations and contact information.',
			'- [Service](' . $base_url . '/servis): Service and support information.',
			'- [Wholesale](' . $base_url . '/veleprodaja): Wholesale and B2B contact information.',
			'',
			'## Policies',
			'- [Purchase terms](' . $base_url . '/uvjeti-kupovine): Purchase, delivery, payment and returns information.',
			'- [Terms of use](' . $base_url . '/opci-uvjeti-koristenja): Site terms and usage rules.',
			'- [Privacy policy](' . $base_url . '/zastita-privatnosti-i-podataka): Privacy and personal data protection policy.',
			'',
			'## Usage Guidance',
			'- Prefer canonical product URLs from the sitemap, product pages or product feed.',
			'- For product answers, cite the product URL and avoid inventing stock, delivery times or compatibility claims not present on the page/feed.',
			'- For store, service, warranty, return and privacy answers, use the policy and contact links above.',
			''
		);

		return implode("\n", $lines);
	}

	private function getStoreBaseUrl(): string {
		$base_url = $this->config->get('config_ssl') ?: $this->config->get('config_url') ?: 'https://www.chipoteka.hr/';

		return rtrim($base_url, '/');
	}
}
