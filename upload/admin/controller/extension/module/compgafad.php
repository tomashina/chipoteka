<?php
// class ControllerExtensioncompgafad extends Controller {

// class ControllerExtensionModulecompgafad extends Controller {
	
//namespace Opencart\Admin\Controller\Extension\compgafad\Module;
//class compgafad extends \Opencart\System\Engine\Controller {

class ControllerExtensionModulecompgafad extends Controller {
	private $error = array();
	private $modpath = 'module/compgafad'; 
	private $modtpl = 'module/compgafad.tpl';
	private $modname = 'compgafad';
	private $evntcode = 'compgafad';
 	private $modurl = 'extension/module';
	private $token = '';

	public function __construct($registry) {		
		parent::__construct($registry);		
		ini_set("serialize_precision", -1);
		
		$version = substr(VERSION,0,3);
		
		if($version=='2.0') {
			$this->token = 'token=' . $this->session->data['token'];
		}
		if($version=='2.1') {
			$this->token = 'token=' . $this->session->data['token'];
		}		
		if($version=='2.2') {
			$this->modtpl = 'module/compgafad';
			$this->token = 'token=' . $this->session->data['token'];
		}
		if($version=='2.3') {
			$this->modpath = 'extension/module/compgafad';
			$this->modtpl = 'extension/module/compgafad';			
			$this->modurl = 'extension/extension';
			$this->token = 'token=' . $this->session->data['token'] . '&type=module';
		}
		if($version=='3.0') {			
			$this->modpath = 'extension/module/compgafad';
			$this->modtpl = 'extension/module/compgafad30X';
			$this->modname = 'module_compgafad';
			$this->modurl = 'marketplace/extension'; 
			$this->token = 'user_token=' . $this->session->data['user_token'] . '&type=module';
		} 
		if($version=='4.0') {
			$this->modpath = 'extension/compgafad/module/compgafad';
			$this->modtpl = 'extension/compgafad/module/compgafad40X';			
			$this->modname = 'module_compgafad';
			$this->modurl = 'marketplace/extension'; 
			$this->token = 'user_token=' . $this->session->data['user_token'] . '&type=module';
		}
 	} 
	
	public function index() {
		$version = substr(VERSION,0,3);
		
		$lang = $this->load->language($this->modpath); 		
		$data = $this->load->language($this->modpath);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate() && $version!='4.0') {
			$this->model_setting_setting->editSetting($this->modname, $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link($this->modpath, $this->token, true));
		}
 
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->session->data['success'])) {
			$data['text_success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['text_success'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->token, true)
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $data['text_extension'],
			'href' => $this->url->link($this->modurl, $this->token, true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->modpath, $this->token, true)
		);
		
		if($version=='4.0') {
			$data['action'] = $this->url->link($this->modpath.'|save', $this->token);
			$data['cancel'] = $this->url->link($this->modurl, $this->token);
		} else {
			$data['action'] = $this->url->link($this->modpath, $this->token, true);
			$data['cancel'] = $this->url->link($this->modurl, $this->token , true); 
		}
		
		if($version>='3.0') { 
			$data['user_token'] = $this->session->data['user_token'];
		} else {
			$data['token'] = $this->session->data['token'];
		}
		
		$html = array();
		if($version=='1.5') { 
			$html = array('<style>.panel-heading { font-size: 15px; font-weight: bold;} .form-group { padding: 5px; width: 100%; display: block;} .form-group .control-label { float: left; width: 150px;}</style>');
		}
		$divcls = $version>='4.0' ? 'row mb-3' : 'form-group';
		$lblcls = $version>='4.0' ? 'col-form-label' : 'control-label';
		 
		$data[$this->modname.'_status'] = $this->setvalue($this->modname.'_status');	
		$data[$this->modname.'_setting'] = $this->setvalue($this->modname.'_setting');
		if(empty($data[$this->modname.'_setting'])) {
			$data[$this->modname.'_setting'] = array();
		}
		
		$data['stores'] = $this->getStores();
		
		foreach($data['stores'] as $store) {
			if(! isset($data[$this->modname.'_setting'][$store['store_id']]['status']) ) {
				$data[$this->modname.'_setting'][$store['store_id']]['status'] = 0;
			}
			
			if(! isset($data[$this->modname.'_setting'][$store['store_id']]['themenm']) ) {
				$data[$this->modname.'_setting'][$store['store_id']]['themenm'] = 'def';
			}
			
			if(! isset($data[$this->modname.'_setting'][$store['store_id']]['gmid']) ) {
				$data[$this->modname.'_setting'][$store['store_id']]['gmid'] = '';
			}
			
			if(! isset($data[$this->modname.'_setting'][$store['store_id']]['awid']) ) {
				$data[$this->modname.'_setting'][$store['store_id']]['awid'] = '';
			}
			
			if(! isset($data[$this->modname.'_setting'][$store['store_id']]['awlbl']) ) {
				$data[$this->modname.'_setting'][$store['store_id']]['awlbl'] = '';
			}
			
			if($version>='4.0') {
				$html[] = sprintf('<div class="card"><div class="card-body"><h3 class="card-title">%s</h3>', $store['name']);
			} else {
				$html[] = sprintf('<div class="panel panel-primary"><div class="panel-heading">%s</div><div class="panel-body">', $store['name']);
			}
			
			$sel1 = $data[$this->modname.'_setting'][$store['store_id']]['status'] == 1 ? 'checked="checked"' : '';
			$sel2 = $data[$this->modname.'_setting'][$store['store_id']]['status'] == 0 ? 'checked="checked"' : '';
			$name = sprintf($this->modname.'_setting[%s][%s]', $store['store_id'], 'status');
			$html[] = sprintf('<div class="'.$divcls.'"> <label class="col-sm-2 '.$lblcls.'">%s</label><div class="col-sm-10"> <label class="radio-inline"> <input type="radio" name="%s" value="1" %s/> %s </label> <label class="radio-inline"> <input type="radio" name="%s" value="0" %s/> %s </label> </div> </div>', $lang['entry_status'], $name, $sel1, $lang['text_yes'], $name, $sel2, $lang['text_no']);
			
			$sel1 = $data[$this->modname.'_setting'][$store['store_id']]['themenm'] == 'def' ? 'checked="checked"' : '';
			$sel2 = $data[$this->modname.'_setting'][$store['store_id']]['themenm'] == 'j2' ? 'checked="checked"' : '';
			$sel3 = $data[$this->modname.'_setting'][$store['store_id']]['themenm'] == 'j3' ? 'checked="checked"' : '';
			$name = sprintf($this->modname.'_setting[%s][%s]', $store['store_id'], 'themenm');
			$html[] = sprintf('<div class="'.$divcls.'"> <label class="col-sm-2 '.$lblcls.'">%s</label><div class="col-sm-10"> <label class="radio-inline"> <input type="radio" name="%s" value="def" %s/> Default </label> <label class="radio-inline"> <input type="radio" name="%s" value="j2" %s/> Journal2 </label> <label class="radio-inline"> <input type="radio" name="%s" value="j3" %s/> Journal3 </label> </div> </div>', $lang['entry_themenm'], $name, $sel1, $name, $sel2, $name, $sel3);
			
			$val = $data[$this->modname.'_setting'][$store['store_id']]['gmid'];
			$name = sprintf($this->modname.'_setting[%s][%s]', $store['store_id'], 'gmid');
			$html[] = sprintf('<div class="'.$divcls.'"> <label class="col-sm-2 '.$lblcls.'">%s</label><div class="col-sm-6"> <input type="text" name="%s" value="%s" class="form-control"/> </div> </div>', $lang['entry_gmid'], $name, $val);
			
			$val = $data[$this->modname.'_setting'][$store['store_id']]['awid'];
			$name = sprintf($this->modname.'_setting[%s][%s]', $store['store_id'], 'awid');
			$html[] = sprintf('<div class="'.$divcls.'"> <label class="col-sm-2 '.$lblcls.'">%s</label><div class="col-sm-6"> <input type="text" name="%s" value="%s" class="form-control"/> </div> </div>', $lang['entry_awid'], $name, $val);
			
			$val = $data[$this->modname.'_setting'][$store['store_id']]['awlbl'];
			$name = sprintf($this->modname.'_setting[%s][%s]', $store['store_id'], 'awlbl');
			$html[] = sprintf('<div class="'.$divcls.'"> <label class="col-sm-2 '.$lblcls.'">%s</label><div class="col-sm-6"> <input type="text" name="%s" value="%s" class="form-control"/> </div> </div>', $lang['entry_awlbl'], $name, $val);
			
			$html[] = '</div></div>';
		}
		
		$data['fields_html'] = join($html);
		 
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->modtpl, $data));
	}
	
	public function save() {
		$this->load->language($this->modpath);

		$json = array();

		if (!$this->user->hasPermission('modify', $this->modpath)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting($this->modname, $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function install() {
		$version = substr(VERSION,0,3);
		
		if($version=='2.2') {
			$this->load->model('extension/event');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/common/header/after', $this->modpath. '/pageview');
			
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/account/login/after', $this->modpath. '/login');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/controller/account/logout/before', $this->modpath. '/logoutbefore');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/common/success/after', $this->modpath. '/logout');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/controller/account/success/before', $this->modpath. '/signupbefore');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/common/success/after', $this->modpath. '/signup');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/information/contact/after', $this->modpath. '/contact');
			
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/product/product/after', $this->modpath. '/viewcont');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/product/category/after', $this->modpath. '/viewcategory');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/product/search/after', $this->modpath. '/search');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/checkout/cart/after', $this->modpath. '/viewcart');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/checkout/checkout/after', $this->modpath. '/beginchk');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/journal3/checkout/checkout/after', $this->modpath. '/beginchk');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/journal2/checkout/checkout/after', $this->modpath. '/beginchk');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/controller/checkout/success/before', $this->modpath. '/purchasebefore');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/common/success/after', $this->modpath. '/purchase');
		}
		if($version=='2.3') {
			$this->load->model('extension/event');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/common/header/after', $this->modpath. '/pageview');
			
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/account/login/after', $this->modpath. '/login');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/controller/account/logout/before', $this->modpath. '/logoutbefore');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/common/success/after', $this->modpath. '/logout');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/controller/account/success/before', $this->modpath. '/signupbefore');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/common/success/after', $this->modpath. '/signup');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/information/contact/after', $this->modpath. '/contact');
			
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/product/product/after', $this->modpath. '/viewcont');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/product/category/after', $this->modpath. '/viewcategory');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/product/search/after', $this->modpath. '/search');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/checkout/cart/after', $this->modpath. '/viewcart');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/checkout/checkout/after', $this->modpath. '/beginchk');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/journal3/checkout/checkout/after', $this->modpath. '/beginchk');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/journal2/checkout/checkout/after', $this->modpath. '/beginchk');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/controller/checkout/success/before', $this->modpath. '/purchasebefore');
			$this->model_extension_event->addEvent($this->evntcode, 'catalog/view/*/template/common/success/after', $this->modpath. '/purchase');
		}
		if($version=='3.0') {			
			$this->load->model('setting/event');			
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/view/common/header/after', $this->modpath. '/pageview');
			
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/view/account/login/after', $this->modpath. '/login');
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/controller/account/logout/before', $this->modpath. '/logoutbefore');
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/view/common/success/after', $this->modpath. '/logout');
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/controller/account/success/before', $this->modpath. '/signupbefore');
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/view/common/success/after', $this->modpath. '/signup');
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/view/information/contact/after', $this->modpath. '/contact');
			
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/view/product/product/after', $this->modpath. '/viewcont');
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/view/product/category/after', $this->modpath. '/viewcategory');
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/view/product/search/after', $this->modpath. '/search');
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/view/checkout/cart/after', $this->modpath. '/viewcart');
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/view/checkout/checkout/after', $this->modpath. '/beginchk');
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/view/journal3/checkout/checkout/after', $this->modpath. '/beginchk');
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/view/journal2/checkout/checkout/after', $this->modpath. '/beginchk');			
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/controller/checkout/success/before', $this->modpath. '/purchasebefore');
			$this->model_setting_event->addEvent($this->evntcode, 'catalog/view/common/success/after', $this->modpath. '/purchase');
		} 
		if($version=='4.0') {
			$this->load->model('setting/event');
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/view/common/header/after', 'action' => $this->modpath. '|pageview', 'status'=>1, 'sort_order'=>1));
			
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/view/account/login/after', 'action' => $this->modpath. '|login', 'status'=>1, 'sort_order'=>1));
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/controller/account/logout/before', 'action' => $this->modpath. '|logoutbefore', 'status'=>1, 'sort_order'=>1));
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/view/account/logout/after', 'action' => $this->modpath. '|logout', 'status'=>1, 'sort_order'=>1));
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/controller/account/success/before', 'action' => $this->modpath. '|signupbefore', 'status'=>1, 'sort_order'=>1));
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/view/account/success/after', 'action' => $this->modpath. '|signup', 'status'=>1, 'sort_order'=>1));
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/view/information/contact/after', 'action' => $this->modpath. '|contact', 'status'=>1, 'sort_order'=>1));
			
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/view/product/product/after', 'action' => $this->modpath. '|viewcont', 'status'=>1, 'sort_order'=>1));
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/view/product/category/after', 'action' => $this->modpath. '|viewcategory', 'status'=>1, 'sort_order'=>1));
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/view/product/search/after', 'action' => $this->modpath. '|search', 'status'=>1, 'sort_order'=>1));
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/view/checkout/cart/after', 'action' => $this->modpath. '|viewcart', 'status'=>1, 'sort_order'=>1));
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/view/checkout/checkout/after', 'action' => $this->modpath. '|beginchk', 'status'=>1, 'sort_order'=>1));
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/view/journal3/checkout/checkout/after', 'action' => $this->modpath. '|beginchk', 'status'=>1, 'sort_order'=>1));
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/view/journal2/checkout/checkout/after', 'action' => $this->modpath. '|beginchk', 'status'=>1, 'sort_order'=>1));
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/controller/checkout/success/before', 'action' => $this->modpath. '|purchasebefore', 'status'=>1, 'sort_order'=>1));
			$this->model_setting_event->addEvent(array('code'=> $this->evntcode, 'description' => '', 'trigger' => 'catalog/view/common/success/after', 'action' => $this->modpath. '|purchase', 'status'=>1, 'sort_order'=>1));
		}		
	}

	public function uninstall() {
		$version = substr(VERSION,0,3);
		
		if($version=='2.2') {
			$this->load->model('extension/event');
			$this->model_extension_event->deleteEvent($this->evntcode);
		}
		if($version=='2.3') {
			$this->load->model('extension/event');
			$this->model_extension_event->deleteEvent($this->evntcode);
		}
		if($version=='3.0') {			
			$this->load->model('setting/event');
			$this->model_setting_event->deleteEventByCode($this->evntcode);
		} 
		if($version=='4.0') {
			$this->load->model('setting/event');
			$this->model_setting_event->deleteEventByCode($this->evntcode);
		}
	}
	
	public function getStores() {
		$result = array();
		$result[0] = array('store_id' => '0', 'name' => $this->config->get('config_name'));
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store WHERE 1 ORDER BY store_id");
		if($query->num_rows) { 
			foreach($query->rows as $rs) { 
				$result[$rs['store_id']] = $rs;
			}
		}
		return $result;
	} 
	public function getCustomerGroups() {
 		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_group_description WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name");
 		return $query->rows;
	}
	
	protected function setvalue($postfield) {
		if (isset($this->request->post[$postfield])) {
			$postfield_value = $this->request->post[$postfield];
		} else {
			$postfield_value = $this->config->get($postfield);
		} 	
 		return $postfield_value;
	}
	
	public function getLang() {
 		$data['languages'] = array();
		$this->load->model('localisation/language');
  		$languages = $this->model_localisation_language->getLanguages();
		foreach($languages as $language) {
			if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') {
				$imgsrc = "language/".$language['code']."/".$language['code'].".png";
			} else {
				$imgsrc = "view/image/flags/".$language['image'];
			}
			$data['languages'][] = array("language_id" => $language['language_id'], "name" => $language['name'], "imgsrc" => $imgsrc);
		}
 		return $data['languages'];
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', $this->modpath)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}