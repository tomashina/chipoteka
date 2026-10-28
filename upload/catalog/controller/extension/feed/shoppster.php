<?php
class ControllerExtensionFeedShoppster extends Controller {
	public function index() {
		$this->load->controller('extension/feed/partner/render', array(
			'code' => 'shoppster',
			'key_parameter' => 'pass'
		));
	}
}
