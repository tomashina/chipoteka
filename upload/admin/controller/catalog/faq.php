<?php
class ControllerCatalogFaq extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('catalog/faq');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('catalog/faq');

		$this->getList();
	}

	public function add() {
		$this->load->language('catalog/faq');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('catalog/faq');

		if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validateForm()) {
			$this->model_catalog_faq->addFaqCategory($this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('catalog/faq', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('catalog/faq');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('catalog/faq');

		if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validateForm()) {
			$this->model_catalog_faq->editFaqCategory((int)$this->request->get['faq_category_id'], $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('catalog/faq', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('catalog/faq');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('catalog/faq');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $faq_category_id) {
				$this->model_catalog_faq->deleteFaqCategory((int)$faq_category_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('catalog/faq', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getList();
	}

	protected function getList() {
		$sort = $this->request->get['sort'] ?? 'fc.sort_order';
		$order = ($this->request->get['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
		$page = max(1, (int)($this->request->get['page'] ?? 1));

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $page;
		}

		$data['breadcrumbs'] = array(
			array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('catalog/faq', 'user_token=' . $this->session->data['user_token'] . $url, true)
			)
		);

		$data['add'] = $this->url->link('catalog/faq/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('catalog/faq/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['faq_categories'] = array();

		$limit = (int)$this->config->get('config_limit_admin');
		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $limit,
			'limit' => $limit
		);

		$total = $this->model_catalog_faq->getTotalFaqCategories();
		$results = $this->model_catalog_faq->getFaqCategories($filter_data);

		foreach ($results as $result) {
			$data['faq_categories'][] = array(
				'faq_category_id' => (int)$result['faq_category_id'],
				'name'            => $result['name'],
				'total_items'     => (int)$result['total_items'],
				'sort_order'      => (int)$result['sort_order'],
				'status'          => (int)$result['status'],
				'edit'            => $this->url->link('catalog/faq/edit', 'user_token=' . $this->session->data['user_token'] . '&faq_category_id=' . (int)$result['faq_category_id'] . $url, true)
			);
		}

		$data['error_warning'] = $this->error['warning'] ?? '';
		$data['success'] = $this->session->data['success'] ?? '';
		unset($this->session->data['success']);

		$data['selected'] = isset($this->request->post['selected']) ? (array)$this->request->post['selected'] : array();

		$sort_url = '&order=' . ($order === 'ASC' ? 'DESC' : 'ASC');

		if (isset($this->request->get['page'])) {
			$sort_url .= '&page=' . $page;
		}

		$data['sort_name'] = $this->url->link('catalog/faq', 'user_token=' . $this->session->data['user_token'] . '&sort=fcd.name' . $sort_url, true);
		$data['sort_status'] = $this->url->link('catalog/faq', 'user_token=' . $this->session->data['user_token'] . '&sort=fc.status' . $sort_url, true);
		$data['sort_sort_order'] = $this->url->link('catalog/faq', 'user_token=' . $this->session->data['user_token'] . '&sort=fc.sort_order' . $sort_url, true);

		$pagination_url = '';

		if (isset($this->request->get['sort'])) {
			$pagination_url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$pagination_url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('catalog/faq', 'user_token=' . $this->session->data['user_token'] . $pagination_url . '&page={page}', true);

		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf(
			$this->language->get('text_pagination'),
			$total ? (($page - 1) * $limit) + 1 : 0,
			min($page * $limit, $total),
			$total,
			$limit ? ceil($total / $limit) : 1
		);

		$data['sort'] = $sort;
		$data['order'] = $order;
		$this->setLanguageData($data);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/faq_list', $data));
	}

	protected function getForm() {
		$faq_category_id = (int)($this->request->get['faq_category_id'] ?? 0);
		$faq_category_info = array();

		if ($faq_category_id && $this->request->server['REQUEST_METHOD'] !== 'POST') {
			$faq_category_info = $this->model_catalog_faq->getFaqCategory($faq_category_id);
		}

		$data['text_form'] = $faq_category_id ? $this->language->get('text_edit') : $this->language->get('text_add');
		$data['error_warning'] = $this->error['warning'] ?? '';
		$data['error_name'] = $this->error['name'] ?? array();
		$data['error_question'] = $this->error['question'] ?? array();
		$data['error_answer'] = $this->error['answer'] ?? array();

		$data['breadcrumbs'] = array(
			array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('catalog/faq', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		if ($faq_category_id) {
			$data['action'] = $this->url->link('catalog/faq/edit', 'user_token=' . $this->session->data['user_token'] . '&faq_category_id=' . $faq_category_id, true);
		} else {
			$data['action'] = $this->url->link('catalog/faq/add', 'user_token=' . $this->session->data['user_token'], true);
		}

		$data['cancel'] = $this->url->link('catalog/faq', 'user_token=' . $this->session->data['user_token'], true);
		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['faq_category_description'])) {
			$data['faq_category_description'] = $this->request->post['faq_category_description'];
		} elseif ($faq_category_id) {
			$data['faq_category_description'] = $this->model_catalog_faq->getFaqCategoryDescriptions($faq_category_id);
		} else {
			$data['faq_category_description'] = array();
		}

		if (isset($this->request->post['faq_item'])) {
			$data['faq_item'] = $this->request->post['faq_item'];
		} elseif ($faq_category_id) {
			$data['faq_item'] = $this->model_catalog_faq->getFaqItems($faq_category_id);
		} else {
			$data['faq_item'] = array();
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = (int)$this->request->post['status'];
		} elseif ($faq_category_info) {
			$data['status'] = (int)$faq_category_info['status'];
		} else {
			$data['status'] = 1;
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = (int)$this->request->post['sort_order'];
		} elseif ($faq_category_info) {
			$data['sort_order'] = (int)$faq_category_info['sort_order'];
		} else {
			$data['sort_order'] = 0;
		}

		$this->setLanguageData($data);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/faq_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'catalog/faq')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach (($this->request->post['faq_category_description'] ?? array()) as $language_id => $description) {
			$name = trim((string)($description['name'] ?? ''));

			if (utf8_strlen($name) < 1 || utf8_strlen($name) > 128) {
				$this->error['name'][(int)$language_id] = $this->language->get('error_name');
			}
		}

		$this->load->model('localisation/language');

		foreach ($this->model_localisation_language->getLanguages() as $language) {
			$language_id = (int)$language['language_id'];

			if (!isset($this->request->post['faq_category_description'][$language_id])) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}
		}

		foreach (($this->request->post['faq_item'] ?? array()) as $language_id => $items) {
			foreach ((array)$items as $row => $item) {
				$question = trim((string)($item['question'] ?? ''));
				$answer = trim((string)($item['answer'] ?? ''));
				$answer_text = $this->getPlainText($answer);

				if ($question === '' && $answer_text === '') {
					continue;
				}

				if ($question === '' || utf8_strlen($question) > 500) {
					$this->error['question'][(int)$language_id][(int)$row] = $this->language->get('error_question');
				}

				if ($answer_text === '') {
					$this->error['answer'][(int)$language_id][(int)$row] = $this->language->get('error_answer');
				}
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	private function getPlainText($html) {
		$text = preg_replace('/<br\s*\/?>/i', "\n", (string)$html);
		$text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');

		return trim(str_replace("\xC2\xA0", ' ', $text));
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'catalog/faq')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	private function setLanguageData(array &$data) {
		$keys = array(
			'heading_title',
			'text_list',
			'text_add',
			'text_edit',
			'text_enabled',
			'text_disabled',
			'text_no_results',
			'text_confirm',
			'text_questions_help',
			'column_name',
			'column_questions',
			'column_status',
			'column_sort_order',
			'column_action',
			'entry_name',
			'entry_question',
			'entry_answer',
			'entry_status',
			'entry_sort_order',
			'button_add',
			'button_edit',
			'button_delete',
			'button_save',
			'button_cancel',
			'button_remove',
			'button_add_question'
		);

		foreach ($keys as $key) {
			$data[$key] = $this->language->get($key);
		}
	}
}
