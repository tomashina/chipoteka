<?php
class ControllerInformationServis extends Controller {
	public function index() {

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		if (isset($this->request->get['servis_id'])) {
			$servis_id = (int)$this->request->get['servis_id'];
		} else {
			$servis_id = 0;
		}

		//$servis_info = $this->model_catalog_information->getInformation($servis_id);

        $this->document->setTitle('meta_title');
        $this->document->setDescription('meta_description');
        $this->document->setKeywords('meta_keyword');

        $data['breadcrumbs'][] = array(
            'text' => 'title',
            'href' => $this->url->link('information/servis', 'servis_id=' .  $servis_id)
        );

        $data['heading_title'] = 'title';

        $data['description'] = html_entity_decode('description', ENT_QUOTES, 'UTF-8');

        $data['continue'] = $this->url->link('common/home');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('information/servis', $data));
	}

	public function search() {
        if (isset($this->request->get['servis_id'])) {
            $servis_id = (int)$this->request->get['servis_id'];
        } else {
            $servis_id = 0;
        }

        $json = ['servis' => $servis_id];

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
	}
}
