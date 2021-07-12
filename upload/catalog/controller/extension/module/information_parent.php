<?php
class ControllerExtensionModuleInformationParent extends Controller {
	public function index() {
		$this->load->language('extension/module/information_parent');

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_contact'] = $this->language->get('text_contact');
		$data['text_sitemap'] = $this->language->get('text_sitemap');

		$this->load->model('extension/information_parent');

		$data['informations'] = array();

		foreach ($this->model_extension_information_parent->getInformations() as $result) {
			$data['informations_children'] = array();
			foreach ($this->model_extension_information_parent->getInformations($result['information_id']) as $child_result) {
				$data['informations_children'][] = array(
					'title' => $child_result['title'],
					'href'  => $this->url->link('information/information', 'information_id=' . $child_result['information_id'])
				);
			}
			
			$data['informations'][] = array(
				'title' => $result['title'],
				'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id']),
				'informations_children'  => $data['informations_children'],
			);
		}

		// print_r($data['informations']); die();
		
		$data['contact'] = $this->url->link('information/contact');
		$data['sitemap'] = $this->url->link('information/sitemap');

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/module/information_parent')) {
			return $this->load->view($this->config->get('config_template') . '/template/extension/module/information_parent', $data);
		} else {
			return $this->load->view('extension/module/information_parent', $data);
		}
	}
}