<?php
class ControllerStartupLogin extends Controller {
	public function index() {
		$route = isset($this->request->get['route']) ? $this->request->get['route'] : '';

		$ignore = array(
			'common/login',
			'common/forgotten',
			'common/reset',
            'marketplace/modification/refreshcron',
            'extension/module/luceed_sync/updatePrices',
            'extension/module/luceed_sync/updateQuantities',
            'extension/module/luceed_sync/updateOrderStatuses',
            'extension/module/luceed_sync/checkOrderStatusDuration',
            'extension/module/luceed_sync/importActions',
            'extension/module/luceed_sync/importProducts',
            'extension/module/luceed_sync/updateProducts',
            'extension/module/luceed_sync/checkRevision',
            'extension/module/luceed_sync/importActionPricesLast30Days',
            'extension/module/luceed_sync/importRelatedProducts',
            'extension/module/luceed_sync/importRelatedProducts',
            'extension/module/luceed_sync/updateVpcPrices',
            'extension/module/luceed_sync/updateB2BPrices',
		);

		// User
		$this->registry->set('user', new Cart\User($this->registry));

		if (!$this->user->isLogged() && !in_array($route, $ignore)) {
			return new Action('common/login');
		}

		if (isset($this->request->get['route'])) {
			$ignore = array(
				'common/login',
				'common/logout',
				'common/forgotten',
				'common/reset',
				'error/not_found',
				'error/permission',
                'marketplace/modification/refreshcron',
                'extension/module/luceed_sync/updatePrices',
                'extension/module/luceed_sync/updateQuantities',
                'extension/module/luceed_sync/updateOrderStatuses',
                'extension/module/luceed_sync/checkOrderStatusDuration',
                'extension/module/luceed_sync/importActions',
                'extension/module/luceed_sync/importProducts',
                'extension/module/luceed_sync/updateProducts',
                'extension/module/luceed_sync/checkRevision',
                'extension/module/luceed_sync/importActionPricesLast30Days',
                'extension/module/luceed_sync/importRelatedProducts',
                'extension/module/luceed_sync/updateVpcPrices',
                'extension/module/luceed_sync/updateB2BPrices',


			);

			if (!in_array($route, $ignore) && (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token']) || ($this->request->get['user_token'] != $this->session->data['user_token']))) {
				return new Action('common/login');
			}
		} else {
			if (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token']) || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
				return new Action('common/login');
			}
		}
	}
}
