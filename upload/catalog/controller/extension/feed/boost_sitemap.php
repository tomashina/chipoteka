<?php
class ControllerExtensionFeedBoostSitemap extends Controller {
	public function index() {
		if ($this->config->get('feed_boost_sitemap_status')) {
			$directory = str_replace('system', 'sitemaps', DIR_SYSTEM);
			$files = glob($directory. '*.xml', GLOB_BRACE);
			$base_url = $this->config->get('config_ssl') ?: $this->config->get('config_url');
			
			if (!$files) {
				$files = [];
			}
			
			$output  = '<?xml version="1.0" encoding="UTF-8"?>';
			$output .= '<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			
			foreach ($files as $file) {
				$time = filemtime($file);
				$file = basename($file);
				$explode = explode('_', $file);
				
				if (isset($explode[1])) {
					$store_id = $explode[1];
					
					if ($store_id == (int)$this->config->get('config_store_id')) {
						$output .= '<sitemap>';
						$output .= '<loc>' . $this->xml(rtrim($base_url, '/') . '/sitemaps/' . $file) . '</loc>';
						$output .= '<lastmod>' . date('c', $time) . '</lastmod>';
						$output .= '</sitemap>';
					}
				}
			}
		
			$output .= '</sitemapindex>';

			$this->response->addHeader('Content-Type: application/xml; charset=UTF-8');
			$this->response->addHeader('Cache-Control: public, max-age=3600');
			$this->response->setOutput($output);
		} else {
			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');
			$this->response->setOutput('404 Not Found');
		}
	}

	private function xml($value) {
		return htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
	}
}
