<?php
class ControllerExtensionFeedBazzar extends Controller {
	public function index() {
		$this->load->controller('extension/feed/partner/render', array(
			'code' => 'bazzar',
			'key_parameter' => 'pass'
		));
	}
}
