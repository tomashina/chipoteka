<?php
class ControllerExtensionModuleBanner extends Controller {
	public function index($setting) {
		static $module = 0;

		$this->load->model('design/banner');
		$this->load->model('tool/image');

		//$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
		//$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/opencart.css');
		//$this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.jquery.js');

		$data['banners'] = array();

		$results = $this->model_design_banner->getBanner($setting['banner_id']);

		foreach ($results as $result) {

            // Responsive slika
            if (!empty($result['image_responsive']) && is_file(DIR_IMAGE . $result['image_responsive'])) {
                $image_responsive = $this->model_tool_image->resize($result['image_responsive'], 500, 500);
            } else {
                // fallback na no_image.png ako nema responsive
                $image_responsive = $this->model_tool_image->resize('no_image.png', 500, 500);
            }



            if (is_file(DIR_IMAGE . $result['image'])) {
				$data['banners'][] = array(
					'title' => $result['title'],
					'link'  => $result['link'],
					'image' => $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height']),
                    'image_responsive'        => $image_responsive // koristi se samo responsive
				);
			}
		}

		$data['module'] = $module++;

		return $this->load->view('extension/module/banner', $data);
	}
}