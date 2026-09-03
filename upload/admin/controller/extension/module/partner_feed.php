<?php
class ControllerExtensionModulePartnerFeed extends Controller {
	private const PARTNER_BASE_URL = 'https://chipoteka.pluschip.lin29.host25.com/';

	private $error = array();

	public function install() {
		$this->load->model('extension/module/partner_feed');
		$this->model_extension_module_partner_feed->install();
	}

	public function uninstall() {
		// Partner mappings and access keys are intentionally preserved.
	}

	public function index() {
		$this->load->language('extension/module/partner_feed');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('extension/module/partner_feed');
		$this->model_extension_module_partner_feed->install(false);

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/module/partner_feed', 'user_token=' . $this->session->data['user_token'], true))
		);

		$data['add'] = $this->url->link('extension/module/partner_feed/add', 'user_token=' . $this->session->data['user_token'], true);
		$data['sync_all'] = $this->url->link('extension/module/partner_feed/syncAll', 'user_token=' . $this->session->data['user_token'], true);
		$data['partner_feeds'] = array();

		foreach ($this->model_extension_module_partner_feed->getPartnerFeeds() as $result) {
			$feed_url = '';
			if ($result['feed_status'] && $result['access_key']) {
				$feed_url = self::PARTNER_BASE_URL . 'index.php?route=extension/feed/partner&code=' . rawurlencode($result['code']) . '&key=' . rawurlencode($result['access_key']);
			}

			$data['partner_feeds'][] = array(
				'partner_feed_id'   => $result['partner_feed_id'],
				'name'              => $result['name'],
				'code'              => $result['code'],
				'luceed_partner_id' => $result['luceed_partner_id'],
				'customer_group'    => ($result['customer_group_name'] ? $result['customer_group_name'] : $this->language->get('text_missing_group')) . ' (ID ' . $result['customer_group_id'] . ')',
				'discount_count'    => $result['discount_count'],
				'status'            => $result['status'],
				'feed_status'       => $result['feed_status'],
				'feed_url'          => $feed_url,
				'last_sync_at'      => $result['last_sync_at'],
				'last_sync_status'  => $result['last_sync_status'],
				'last_sync_message' => $result['last_sync_message'],
				'edit'              => $this->url->link('extension/module/partner_feed/edit', 'user_token=' . $this->session->data['user_token'] . '&partner_feed_id=' . $result['partner_feed_id'], true),
				'delete'            => $this->url->link('extension/module/partner_feed/delete', 'user_token=' . $this->session->data['user_token'] . '&partner_feed_id=' . $result['partner_feed_id'], true),
				'sync'              => $this->url->link('extension/module/partner_feed/sync', 'user_token=' . $this->session->data['user_token'] . '&partner_feed_id=' . $result['partner_feed_id'], true),
				'regenerate'        => $this->url->link('extension/module/partner_feed/regenerate', 'user_token=' . $this->session->data['user_token'] . '&partner_feed_id=' . $result['partner_feed_id'], true)
			);
		}

		$data['error_warning'] = isset($this->session->data['error']) ? $this->session->data['error'] : '';
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['error'], $this->session->data['success']);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/partner_feed_list', $data));
	}

	public function add() {
		$this->saveForm();
	}

	public function edit() {
		$this->saveForm();
	}

	public function delete() {
		$this->load->language('extension/module/partner_feed');

		if (!$this->validatePermission()) {
			$this->session->data['error'] = $this->error['warning'];
		} elseif (isset($this->request->get['partner_feed_id'])) {
			$this->load->model('extension/module/partner_feed');
			$this->model_extension_module_partner_feed->deletePartnerFeed($this->request->get['partner_feed_id']);
			$this->session->data['success'] = $this->language->get('text_success_delete');
		}

		$this->response->redirect($this->url->link('extension/module/partner_feed', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function regenerate() {
		$this->load->language('extension/module/partner_feed');

		if (!$this->validatePermission()) {
			$this->session->data['error'] = $this->error['warning'];
		} elseif (isset($this->request->get['partner_feed_id'])) {
			$this->load->model('extension/module/partner_feed');
			$this->model_extension_module_partner_feed->regenerateAccessKey($this->request->get['partner_feed_id'], $this->generateAccessKey());
			$this->session->data['success'] = $this->language->get('text_success_key');
		}

		$this->response->redirect($this->url->link('extension/module/partner_feed', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function sync() {
		$this->runSync(isset($this->request->get['partner_feed_id']) ? array((int)$this->request->get['partner_feed_id']) : array());
	}

	public function syncAll() {
		$this->runSync(array());
	}

	private function runSync($partner_feed_ids) {
		$this->load->language('extension/module/partner_feed');

		if (!$this->validatePermission()) {
			$this->session->data['error'] = $this->error['warning'];
			$this->response->redirect($this->url->link('extension/module/partner_feed', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$this->load->model('extension/module/partner_feed');
		$this->model_extension_module_partner_feed->install(false);

		try {
			$this->syncManufacturerUids();
			$result = $this->model_extension_module_partner_feed->syncMappings($partner_feed_ids);

			if ($partner_feed_ids) {
				$partner = $this->model_extension_module_partner_feed->getPartnerFeed($partner_feed_ids[0]);
				$count = isset($result['partners'][$partner_feed_ids[0]]) ? $result['partners'][$partner_feed_ids[0]] : 0;
				$this->session->data['success'] = sprintf($this->language->get('text_success_sync'), $count, $partner ? $partner['name'] : '');
			} else {
				if (!empty($result['errors'])) {
					$this->session->data['error'] = sprintf($this->language->get('text_partial_sync'), $result['total'], implode(' | ', $result['errors']));
				} else {
					$this->session->data['success'] = sprintf($this->language->get('text_success_sync_all'), $result['total']);
				}
			}
		} catch (Throwable $e) {
			$this->session->data['error'] = sprintf($this->language->get('error_sync'), $e->getMessage());
		}

		$this->response->redirect($this->url->link('extension/module/partner_feed', 'user_token=' . $this->session->data['user_token'], true));
	}

	private function syncManufacturerUids() {
		try {
			(new \Agmedia\LuceedOpencartWrapper\Models\LOC_Manufacturer(
				\Agmedia\Luceed\Facade\LuceedManufacturer::all()
			))->updateByUids();
		} catch (Throwable $e) {
			\Agmedia\Helpers\Log::store('partner_feed(manufacturer_uid_sync) ::: ' . $e->getMessage());
		}
	}

	private function saveForm() {
		$this->load->language('extension/module/partner_feed');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('extension/module/partner_feed');
		$this->model_extension_module_partner_feed->install(false);

		$partner_feed_id = isset($this->request->get['partner_feed_id']) ? (int)$this->request->get['partner_feed_id'] : 0;
		$partner_feed_info = $partner_feed_id ? $this->model_extension_module_partner_feed->getPartnerFeed($partner_feed_id) : array();

		if ($partner_feed_id && !$partner_feed_info) {
			$this->session->data['error'] = $this->language->get('error_not_found');
			$this->response->redirect($this->url->link('extension/module/partner_feed', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm($partner_feed_id)) {
			$data = $this->request->post;
			$data['name'] = trim($data['name']);
			$data['code'] = $this->slugify($data['code'] ? $data['code'] : $data['name']);
			$data['luceed_partner_id'] = trim($data['luceed_partner_id']);
			$data['new_customer_group_name'] = isset($data['new_customer_group_name']) ? trim($data['new_customer_group_name']) : '';
			$data['status'] = !empty($data['status']) ? 1 : 0;
			$data['feed_status'] = !empty($data['feed_status']) ? 1 : 0;
			$data['sort_order'] = (int)$data['sort_order'];

			if (empty($data['customer_group_id'])) {
				$data['customer_group_id'] = $this->createCustomerGroup($data['new_customer_group_name'] ? $data['new_customer_group_name'] : $data['name']);
			}

			if ($partner_feed_id) {
				$data['access_key'] = !empty($partner_feed_info['access_key']) ? $partner_feed_info['access_key'] : $this->generateAccessKey();
				$this->model_extension_module_partner_feed->editPartnerFeed($partner_feed_id, $data);
			} else {
				$data['access_key'] = $this->generateAccessKey();
				$partner_feed_id = $this->model_extension_module_partner_feed->addPartnerFeed($data);
			}

			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/module/partner_feed', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data['text_form'] = $partner_feed_id ? $this->language->get('text_edit') : $this->language->get('text_add');
		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/module/partner_feed', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $data['text_form'], 'href' => '')
		);

		$route = $partner_feed_id ? 'extension/module/partner_feed/edit' : 'extension/module/partner_feed/add';
		$params = 'user_token=' . $this->session->data['user_token'] . ($partner_feed_id ? '&partner_feed_id=' . $partner_feed_id : '');
		$data['action'] = $this->url->link($route, $params, true);
		$data['cancel'] = $this->url->link('extension/module/partner_feed', 'user_token=' . $this->session->data['user_token'], true);

		$fields = array('name', 'code', 'luceed_partner_id', 'customer_group_id', 'new_customer_group_name', 'status', 'feed_status', 'sort_order');
		foreach ($fields as $field) {
			if (isset($this->request->post[$field])) {
				$data[$field] = $this->request->post[$field];
			} elseif (isset($partner_feed_info[$field])) {
				$data[$field] = $partner_feed_info[$field];
			} else {
				$data[$field] = in_array($field, array('status', 'feed_status')) ? 1 : '';
			}
		}

		$data['errors'] = $this->error;
		$this->load->model('customer/customer_group');
		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/partner_feed_form', $data));
	}

	private function validateForm($partner_feed_id) {
		if (!$this->validatePermission()) {
			return false;
		}

		$name = isset($this->request->post['name']) ? trim($this->request->post['name']) : '';
		$code = $this->slugify(isset($this->request->post['code']) && $this->request->post['code'] ? $this->request->post['code'] : $name);
		$luceed_partner_id = isset($this->request->post['luceed_partner_id']) ? trim($this->request->post['luceed_partner_id']) : '';
		$customer_group_id = isset($this->request->post['customer_group_id']) ? (int)$this->request->post['customer_group_id'] : 0;

		if (utf8_strlen($name) < 2 || utf8_strlen($name) > 128) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (!$code || utf8_strlen($code) > 64) {
			$this->error['code'] = $this->language->get('error_code');
		} else {
			$owner = $this->model_extension_module_partner_feed->getCodeOwner($code);
			if ($owner && $owner !== (int)$partner_feed_id) {
				$this->error['code'] = $this->language->get('error_code_exists');
			}
		}

		if (!$luceed_partner_id || utf8_strlen($luceed_partner_id) > 128) {
			$this->error['luceed_partner_id'] = $this->language->get('error_luceed_partner_id');
		} else {
			$owner = $this->model_extension_module_partner_feed->getLuceedPartnerOwner($luceed_partner_id);
			if ($owner && $owner !== (int)$partner_feed_id) {
				$this->error['luceed_partner_id'] = $this->language->get('error_luceed_partner_exists');
			}
		}

		if ($customer_group_id) {
			if (!$this->model_extension_module_partner_feed->customerGroupExists($customer_group_id)) {
				$this->error['customer_group_id'] = $this->language->get('error_customer_group_missing');
			}
		} else {
			$new_group_name = isset($this->request->post['new_customer_group_name']) ? trim($this->request->post['new_customer_group_name']) : '';
			if (utf8_strlen($new_group_name) < 2 || utf8_strlen($new_group_name) > 32) {
				$this->error['customer_group_id'] = $this->language->get('error_new_customer_group');
			}
		}

		return !$this->error;
	}

	private function validatePermission() {
		if (!$this->user->hasPermission('modify', 'extension/module/partner_feed')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !isset($this->error['warning']);
	}

	private function createCustomerGroup($name) {
		$this->load->model('localisation/language');
		$this->load->model('customer/customer_group');
		$descriptions = array();

		foreach ($this->model_localisation_language->getLanguages() as $language) {
			$descriptions[$language['language_id']] = array('name' => $name, 'description' => 'Luceed partner ' . $name);
		}

		return $this->model_customer_customer_group->addCustomerGroup(array(
			'approval' => 0,
			'sort_order' => 99,
			'customer_group_description' => $descriptions
		));
	}

	private function slugify($value) {
		$value = strtr(utf8_strtolower(trim($value)), array('č' => 'c', 'ć' => 'c', 'đ' => 'd', 'š' => 's', 'ž' => 'z'));
		$value = preg_replace('/[^a-z0-9]+/', '-', $value);

		return trim($value, '-');
	}

	private function generateAccessKey() {
		try {
			return bin2hex(random_bytes(24));
		} catch (Exception $e) {
			return token(48);
		}
	}
}
