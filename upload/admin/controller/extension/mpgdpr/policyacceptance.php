<?php
set_time_limit(0);
ini_set('memory_limit', '999M');
ini_set('set_time_limit', '0');

// Autoloader
require_once(DIR_SYSTEM.'library/mpgdpr/composer/vendor/autoload.php');

// https://www.dropzonejs.com/#configuration
class ControllerExtensionMpGdprPolicyAcceptance extends \Mpgdpr\Controller {
	use \Mpgdpr\trait_mpgdpr;
	private $error = [];

	public function __construct($registry) {
		parent :: __construct($registry);
		$this->igniteTraitMpGdpr($registry);
	}

	public function index() {
		$this->load->language($this->extension_path . 'mpgdpr/policyacceptance');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addStyle('view/stylesheet/mpgdpr/mpgdpr.css');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

		if (isset($this->request->get['filter_request_id'])) {
			$filter_request_id = $this->request->get['filter_request_id'];
		} else {
			$filter_request_id = '';
		}

		if (isset($this->request->get['filter_type'])) {
			$filter_type = $this->request->get['filter_type'];
		} else {
			$filter_type = null;
		}

		if (isset($this->request->get['filter_email'])) {
			$filter_email = $this->request->get['filter_email'];
		} else {
			$filter_email = null;
		}

		if (isset($this->request->get['filter_useragent'])) {
			$filter_useragent = $this->request->get['filter_useragent'];
		} else {
			$filter_useragent = null;
		}

		if (isset($this->request->get['filter_server_ip'])) {
			$filter_server_ip = $this->request->get['filter_server_ip'];
		} else {
			$filter_server_ip = null;
		}

		if (isset($this->request->get['filter_client_ip'])) {
			$filter_client_ip = $this->request->get['filter_client_ip'];
		} else {
			$filter_client_ip = null;
		}

		if (isset($this->request->get['filter_date_start'])) {
			$filter_date_start = $this->request->get['filter_date_start'];
		} else {
			$filter_date_start = null;
		}

		if (isset($this->request->get['filter_date_end'])) {
			$filter_date_end = $this->request->get['filter_date_end'];
		} else {
			$filter_date_end = null;
		}

		if (isset($this->request->get['filter_time_lap_value'])) {
			$filter_time_lap_value = $this->request->get['filter_time_lap_value'];
		} else {
			$filter_time_lap_value = null;
		}

		if (isset($this->request->get['filter_time_lap'])) {
			$filter_time_lap = $this->request->get['filter_time_lap'];
		} else {
			$filter_time_lap = null;
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'r.date_added';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = $this->filters();

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->token.'=' . $this->session->data[$this->token], true)
		];

		$this->breadcrumbs($data);

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->extension_path . 'mpgdpr/policyacceptance', $this->token.'=' . $this->session->data[$this->token] . $url, true)
		];

		if (VERSION < '3.0.0.0') {
			$this->getAllLanguageMpgdpr($data);
		}

		$data['token'] = $this->session->data[$this->token];
		$data['get_token'] = $this->token;
		$data['extension_path'] = $this->extension_path;

		$data['delete'] = $this->url->link($this->extension_path . 'mpgdpr/policyacceptance/delete', $this->token.'=' . $this->session->data[$this->token] . $url, true);
		$data['export'] = $this->url->link($this->extension_path . 'mpgdpr/policyacceptance/export', $this->token.'=' . $this->session->data[$this->token], true);

		$data['requests'] = [];

		$filter_data = [
			'sort'  => $sort,
			'order' => $order,
			'filter_request_id' => $filter_request_id,
			'filter_type' => $filter_type,
			'filter_email' => $filter_email,
			'filter_useragent' => $filter_useragent,
			'filter_server_ip' => $filter_server_ip,
			'filter_client_ip' => $filter_client_ip,
			'filter_date_start' => $filter_date_start,
			'filter_date_end' => $filter_date_end,
			'filter_time_lap_value' => $filter_time_lap_value,
			'filter_time_lap' => $filter_time_lap,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		];

		$policyacceptance_total = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getTotalPolicyAcceptances($filter_data);

		$results = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getPolicyAcceptances($filter_data);

		$customer_model = $this->customerModelObj();

		foreach ($results as $result) {

			// 01-05-2022: updation start
			if (empty($result['policy_title']) || empty($result['policy_description'])) {
				$information = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getInformation($result['policy_id']);
				if ($information) {
					if (empty($result['policy_title'])) {
						$result['policy_title'] = $information['title'];
					}
					if (empty($result['policy_description'])) {
						$result['policy_description'] = $information['description'];
					}
				}
			}
			// 01-05-2022: updation end

			$customer_info = $this->{$customer_model}->getCustomer($result['customer_id']);
			$email = $result['email'];
			if ($customer_info) {
				$email = $customer_info['email'];
			}

			$data['requests'][] = [
				'mpgdpr_policyacceptance_id' => $result['mpgdpr_policyacceptance_id'],
				'policy_view' => $this->url->link($this->extension_path . 'mpgdpr/policyacceptance/policyView', $this->token.'=' . $this->session->data[$this->token].'&mpgdpr_policyacceptance_id='.$result['mpgdpr_policyacceptance_id'], true),
				'policy_download' => $this->url->link($this->extension_path . 'mpgdpr/policyacceptance/policyDownload', $this->token.'=' . $this->session->data[$this->token].'&information_id='.$result['policy_id'], true),
				'policy_id' => $result['policy_id'],
				'policy_title' => $result['policy_title'],
				'policy_description' => substr( strip_tags(html_entity_decode($result['policy_description'], ENT_QUOTES, 'UTF-8')), 0 , 20) .'..',
				'email'        => $email,
				'type'        => $this->mpgdpr->getRequestName($result['requessttype']),
				'acceptlanguage'        => $result['accept_language'],
				'useragent'        => $result['user_agent'],
				'server_ip'        => $result['server_ip'],
				'client_ip'        => $result['client_ip'],
				/*13 sep 2019 gdpr session starts*/
				'date_added'        => date($this->language->get('datetime_format'), strtotime($result['date_added'])) ,
				/*13 sep 2019 gdpr session ends*/
			];
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} elseif (isset($this->session->data['warning'])) {
			$data['error_warning'] = $this->session->data['warning'];
			unset($this->session->data['warning']);
		} else {
			$data['error_warning'] = '';
		}


		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = [];
		}

		$url = $this->filters(array('sort', 'order'));

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		$data['sort_policyacceptance_id'] = $this->url->link($this->extension_path . 'mpgdpr/policyacceptance', $this->token.'=' . $this->session->data[$this->token] . '&sort=pa.mpgdpr_policyacceptance_id' . $url, true);
		$data['sort_requessttype'] = $this->url->link($this->extension_path . 'mpgdpr/policyacceptance', $this->token.'=' . $this->session->data[$this->token] . '&sort=pa.requessttype' . $url, true);
		$data['sort_policy_id'] = $this->url->link($this->extension_path . 'mpgdpr/policyacceptance', $this->token.'=' . $this->session->data[$this->token] . '&sort=pa.policy_id' . $url, true);
		$data['sort_email'] = $this->url->link($this->extension_path . 'mpgdpr/policyacceptance', $this->token.'=' . $this->session->data[$this->token] . '&sort=pa.email' . $url, true);
		$data['sort_date_added'] = $this->url->link($this->extension_path . 'mpgdpr/policyacceptance', $this->token.'=' . $this->session->data[$this->token] . '&sort=pa.date_added' . $url, true);

		$url = $this->filters(array('page'));

		$pagination = new Pagination();
		$pagination->total = $policyacceptance_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link($this->extension_path . 'mpgdpr/policyacceptance', $this->token.'=' . $this->session->data[$this->token] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($policyacceptance_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($policyacceptance_total - $this->config->get('config_limit_admin'))) ? $policyacceptance_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $policyacceptance_total, ceil($policyacceptance_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['filter_request_id'] = $filter_request_id;
		$data['filter_type'] = $filter_type;
		$data['filter_email'] = $filter_email;
		$data['filter_useragent'] = $filter_useragent;
		$data['filter_server_ip'] = $filter_server_ip;
		$data['filter_client_ip'] = $filter_client_ip;
		$data['filter_date_start'] = $filter_date_start;
		$data['filter_date_end'] = $filter_date_end;
		$data['filter_time_lap_value'] = $filter_time_lap_value;
		$data['filter_time_lap'] = $filter_time_lap;
		/*13 sep 2019 gdpr session starts*/
		// 01-05-2022: updation start
		$data['request_types'] = $this->mpgdpr->getRequestTypes([
			\Mpgdpr\Mpgdpr :: CODEPOLICYACCEPTCONTACTUS,
			\Mpgdpr\Mpgdpr :: CODEPOLICYACCEPTREGISTER,
			\Mpgdpr\Mpgdpr :: CODEPOLICYACCEPTCHECKOUT,
			\Mpgdpr\Mpgdpr :: CODEPOLICYACCEPTCOOKIECONSENT,
		]);
		// 01-05-2022: updation end
		/*13 sep 2019 gdpr session ends*/

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->viewLoad($this->extension_path . 'mpgdpr/policyacceptance', $data));
	}

	protected function filters($excludes = array()) {
		$url = '';

		if (isset($this->request->get['filter_request_id']) && !in_array('filter_request_id', $excludes)) {
			$url .= '&filter_request_id=' . $this->request->get['filter_request_id'];
		}

		if (isset($this->request->get['filter_type']) && !in_array('filter_type', $excludes)) {
			$url .= '&filter_type=' . urlencode(html_entity_decode($this->request->get['filter_type'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_email']) && !in_array('filter_email', $excludes)) {
			$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_useragent']) && !in_array('filter_useragent', $excludes)) {
			$url .= '&filter_useragent=' . urlencode(html_entity_decode($this->request->get['filter_useragent'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_server_ip']) && !in_array('filter_server_ip', $excludes)) {
			$url .= '&filter_server_ip=' . urlencode(html_entity_decode($this->request->get['filter_server_ip'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_client_ip']) && !in_array('filter_client_ip', $excludes)) {
			$url .= '&filter_client_ip=' . urlencode(html_entity_decode($this->request->get['filter_client_ip'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_date_start']) && !in_array('filter_date_start', $excludes)) {
			$url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
		}

		if (isset($this->request->get['filter_date_end']) && !in_array('filter_date_end', $excludes)) {
			$url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
		}

		if (isset($this->request->get['filter_time_lap_value']) && !in_array('filter_time_lap_value', $excludes)) {
			$url .= '&filter_time_lap_value=' . urlencode(html_entity_decode($this->request->get['filter_time_lap_value'], ENT_QUOTES, 'UTF-8'));
		}
		if (isset($this->request->get['filter_time_lap']) && !in_array('filter_time_lap', $excludes)) {
			$url .= '&filter_time_lap=' . urlencode(html_entity_decode($this->request->get['filter_time_lap'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['sort']) && !in_array('sort', $excludes)) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order']) && !in_array('order', $excludes)) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page']) && !in_array('page', $excludes)) {
			$url .= '&page=' . $this->request->get['page'];
		}

		return $url;
	}

	public function delete() {
		$this->load->language($this->extension_path . 'mpgdpr/policyacceptance');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $mpgdpr_policyacceptance_id) {
				$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->deletePolicyAcceptance($mpgdpr_policyacceptance_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->filters();

			$this->response->redirect($this->url->link($this->extension_path . 'mpgdpr/policyacceptance', $this->token.'=' . $this->session->data[$this->token] . $url, true));
		}

		$this->index();
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', $this->extension_path . 'mpgdpr/policyacceptance')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function export() {
		$this->load->language($this->extension_path . 'mpgdpr/policyacceptance');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

		$results = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getPolicyAcceptances();
		$customer_model = $this->customerModelObj();

		// 29 dec 2022 changes starts
		$file_name = 'policyacceptancedata.xls';
		$file_format = 'csv';
		if (in_array($this->config->get('mpgdpr_export_format'), ['csv','xls','xlsx','json','xml'])) {
			$file_format = $this->config->get('mpgdpr_export_format');
		}

		// process all data

		if (in_array($file_format, ['csv','xls','xlsx'])) {

			$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

			$objPHPExcel->setActiveSheetIndex(0);


			$i = 1;
			$char = 'A';

			$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_policyacceptance_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
			$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_type'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
			$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_policy_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
			$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_policy_title'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
			$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_policy_description'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
			$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_server_ip'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
			$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_client_ip'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
			$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_email'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
			$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_useragent'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
			$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_acceptlanguage'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
			$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_date'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);

			// Background Color
			$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('1A017FBE');
			// Font Color
			$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF');

			// insert data in excel

			foreach ($results as $result) {

				// 01-05-2022: updation start
				if (empty($result['policy_title']) || empty($result['policy_description'])) {
					$information = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getInformation($result['policy_id']);
					if ($information) {
						if (empty($result['policy_title'])) {
							$result['policy_title'] = $information['title'];
						}
						if (empty($result['policy_description'])) {
							$result['policy_description'] = $information['description'];
						}
					}
				}
				// 01-05-2022: updation end

				$customer_info = $this->{$customer_model}->getCustomer($result['customer_id']);
				$email = $result['email'];
				if ($customer_info) {
					$email = $customer_info['email'];
				}

				$char_value = 'A'; $i++;
				$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['mpgdpr_policyacceptance_id']);
				$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $this->mpgdpr->getRequestName($result['requessttype']));
				$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['policy_id']);
				$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['policy_title']);
				$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, html_entity_decode($result['policy_description'], ENT_QUOTES, 'UTF-8'));
				$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['server_ip']);
				$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['client_ip']);
				$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $email);
				$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['user_agent']);
				$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['accept_language']);
				$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, date($this->language->get('date_format_short'), strtotime($result['date_added'])));
			}

			// Find Format

			if ($file_format == 'xls') {
				$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($objPHPExcel);
				$file_name = 'policyacceptancedata.xls';
			} elseif ($file_format == 'xlsx') {
				$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
				$file_name = 'policyacceptancedata.xlsx';
			} elseif ($file_format == 'csv') {
				$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($objPHPExcel);
				$file_name = 'policyacceptancedata.csv';
			} else {
				$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
				$file_name = 'policyacceptancedata.xlsx';
			}

			$file_to_save = DIR_UPLOAD . $file_name;
			$objWriter->save(DIR_UPLOAD . $file_name);
		}

		if ($file_format == 'json') {
			$export_data = [];

			foreach ($results as $result) {
				// 01-05-2022: updation start
				if (empty($result['policy_title']) || empty($result['policy_description'])) {
					$information = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getInformation($result['policy_id']);
					if ($information) {
						if (empty($result['policy_title'])) {
							$result['policy_title'] = $information['title'];
						}
						if (empty($result['policy_description'])) {
							$result['policy_description'] = $information['description'];
						}
					}
				}
				// 01-05-2022: updation end

				$customer_info = $this->{$customer_model}->getCustomer($result['customer_id']);
				$email = $result['email'];
				if ($customer_info) {
					$email = $customer_info['email'];
				}

				$export_data[] = [
					'policyacceptance_id' => [
						'text' => $this->language->get('export_policyacceptance_id'),
						'value' => $result['mpgdpr_policyacceptance_id']
					],
					'type' => [
						'text' => $this->language->get('export_type'),
						'value' => $result['requessttype']
					],
					'policy_id' => [
						'text' => $this->language->get('export_policy_id'),
						'value' => $result['policy_id']
					],
					'policy_title' => [
						'text' => $this->language->get('export_policy_title'),
						'value' => $result['policy_title']
					],
					'policy_description' => [
						'text' => $this->language->get('export_policy_description'),
						'value' => html_entity_decode($result['policy_description'], ENT_QUOTES, 'UTF-8')
					],
					'server_ip' => [
						'text' => $this->language->get('export_server_ip'),
						'value' => $result['server_ip']
					],
					'client_ip' => [
						'text' => $this->language->get('export_client_ip'),
						'value' => $result['client_ip']
					],
					'email' => [
						'text' => $this->language->get('export_email'),
						'value' => $email
					],
					'useragent' => [
						'text' => $this->language->get('export_useragent'),
						'value' => $result['user_agent']
					],
					'acceptlanguage' => [
						'text' => $this->language->get('export_acceptlanguage'),
						'value' => $result['accept_language']
					],
					'date' => [
						'text' => $this->language->get('export_date'),
						'value' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
					]
				];
			}

			// create a file with name.json
			$file_name = 'policyacceptancedata.json';
			$file_to_save = DIR_UPLOAD . $file_name;

			$handle = fopen($file_to_save, "w");

			fwrite($handle, json_encode($export_data, JSON_PRETTY_PRINT));
			fclose($handle);
		}

		if ($file_format == 'xml') {
			$export_data = [];

			$xml = new \DOMDocument('1.0', 'UTF-8');

	    	$xml->preserveWhiteSpace = false;
			$xml->formatOutput = true;

			$xml_policyacceptancedatas = $xml->createElement("policyacceptancedatas");
			$xml->appendChild($xml_policyacceptancedatas);

			foreach ($results as $result) {
				$xml_policyacceptancedata = $xml->createElement("policyacceptancedata");
				$xml_policyacceptancedatas->appendChild($xml_policyacceptancedata);

				$export_data = [];

				// 01-05-2022: updation start
				if (empty($result['policy_title']) || empty($result['policy_description'])) {
					$information = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getInformation($result['policy_id']);
					if ($information) {
						if (empty($result['policy_title'])) {
							$result['policy_title'] = $information['title'];
						}
						if (empty($result['policy_description'])) {
							$result['policy_description'] = $information['description'];
						}
					}
				}
				// 01-05-2022: updation end

				$customer_info = $this->{$customer_model}->getCustomer($result['customer_id']);
				$email = $result['email'];
				if ($customer_info) {
					$email = $customer_info['email'];
				}

				$export_data = [
					'policyacceptance_id' => [
						'text' => $this->language->get('export_policyacceptance_id'),
						'value' => $result['mpgdpr_policyacceptance_id']
					],
					'type' => [
						'text' => $this->language->get('export_type'),
						'value' => $result['requessttype']
					],
					'policy_id' => [
						'text' => $this->language->get('export_policy_id'),
						'value' => $result['policy_id']
					],
					'policy_title' => [
						'text' => $this->language->get('export_policy_title'),
						'value' => $result['policy_title']
					],
					'policy_description' => [
						'text' => $this->language->get('export_policy_description'),
						'value' => html_entity_decode($result['policy_description'], ENT_QUOTES, 'UTF-8')
					],
					'server_ip' => [
						'text' => $this->language->get('export_server_ip'),
						'value' => $result['server_ip']
					],
					'client_ip' => [
						'text' => $this->language->get('export_client_ip'),
						'value' => $result['client_ip']
					],
					'email' => [
						'text' => $this->language->get('export_email'),
						'value' => $email
					],
					'useragent' => [
						'text' => $this->language->get('export_useragent'),
						'value' => $result['user_agent']
					],
					'acceptlanguage' => [
						'text' => $this->language->get('export_acceptlanguage'),
						'value' => $result['accept_language']
					],
					'date' => [
						'text' => $this->language->get('export_date'),
						'value' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
					]
				];

				foreach ($export_data as $key => $edata) {
					if ($edata['value'] == '') {
						$edata['value'] = ' ';
					}

					$xml_edata = $xml->createElement($key, $edata['value']);
					// $xml_edata->setAttribute("text", $edata['text']);
					$xml_policyacceptancedata->appendChild($xml_edata);

					// $xml_attr = $xml->createAttribute('text');
					// $xml_attr->value = $edata['text'];
					// $xml_edata->appendChild($xml_attr);
				}

			}

			$file_name = 'policyacceptancedata.xml';
			$file_to_save = DIR_UPLOAD . $file_name;

			// echo $xml->saveXML();
			$xml->save($file_to_save);

		}

		$this->response->redirect($this->url->link($this->extension_path . 'mpgdpr/policyacceptance/fileDownload', $this->token.'='.$this->session->data[$this->token].'&file_name='. $file_name .'&file_format='. $file_format, true));
		// 29 dec 2022 changes ends
	}


	public function policyView() {
		$this->load->language($this->extension_path . 'mpgdpr/policyacceptance');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

		$data['error'] = $this->language->get('error_not_exists');
		$data['title'] = $this->language->get('error_not_exists');
		$result = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getPolicyAcceptance($this->request->get['mpgdpr_policyacceptance_id']);

		if ($result) {
			// 01-05-2022: updation start
			if (empty($result['policy_title']) || empty($result['policy_description'])) {
				$information = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getInformation($result['policy_id']);
				if ($information) {
					if (empty($result['policy_title'])) {
						$result['policy_title'] = $information['title'];
					}
					if (empty($result['policy_description'])) {
						$result['policy_description'] = $information['description'];
					}
				}
			}
			// 01-05-2022: updation end
			$data['policy_title'] = $result['policy_title'];
			$data['policy_description'] = html_entity_decode($result['policy_description'], ENT_QUOTES, 'UTF-8');
			$data['title'] = $result['policy_title'];
		}

		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');

		$this->response->setOutput($this->viewLoad($this->extension_path . 'mpgdpr/policyview', $data));
	}

	public function policyDownload() {
		$json = [];

		$this->load->language($this->extension_path . 'mpgdpr/policyacceptance');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');


		if (empty($this->request->post['mpgdpr_policyacceptance_id'])) {
			$json['error'] = $this->language->get('error_invalid');
		}

		if (!$json) {
			$result = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getPolicyAcceptance($this->request->post['mpgdpr_policyacceptance_id']);
			if (empty($result)) {
				$json['error'] = $this->language->get('error_not_exists');
			} else {

				// 01-05-2022: updation start
				if (empty($result['policy_title']) || empty($result['policy_description'])) {
					$information = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getInformation($result['policy_id']);
					if ($information) {
						if (empty($result['policy_title'])) {
							$result['policy_title'] = $information['title'];
						}
						if (empty($result['policy_description'])) {
							$result['policy_description'] = $information['description'];
						}
					}
				}
				// 01-05-2022: updation end

				// 29 dec 2022 changes starts
				$file_name = 'policydata.xls';
				$file_format = 'csv';
				if (in_array($this->config->get('mpgdpr_export_format'), ['csv','xls','xlsx','json','xml'])) {
					$file_format = $this->config->get('mpgdpr_export_format');
				}

				// process all data

				if (in_array($file_format, ['csv','xls','xlsx'])) {

					$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

					$i = 1;
					$char = 'A';

					$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_policyacceptance_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
					$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_policy_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
					$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_policy_title'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
					$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_policy_description'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);


					// insert data in excel
					$char_value = 'A'; $i++;
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['mpgdpr_policyacceptance_id']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['policy_id']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['policy_title']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, html_entity_decode($result['policy_description'], ENT_QUOTES, 'UTF-8'));


					if ($file_format == 'xls') {
						$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($objPHPExcel);
						$file_name = 'policydata.xls';
					} elseif ($file_format == 'xlsx') {
						$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
						$file_name = 'policydata.xlsx';
					} elseif ($file_format == 'csv') {
						$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($objPHPExcel);
						$file_name = 'policydata.csv';
					} else {
						$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
						$file_name = 'policydata.xlsx';
					}

					$file_to_save = DIR_UPLOAD . $file_name;
					$objWriter->save(DIR_UPLOAD . $file_name);

				}

				if ($file_format == 'json') {
					$export_data = [];

					$export_data[] = [
						'policyacceptance_id' => [
							'text' => $this->language->get('export_policyacceptance_id'),
							'value' => $result['mpgdpr_policyacceptance_id']
						],
						'policy_id' => [
							'text' => $this->language->get('export_policy_id'),
							'value' => $result['policy_id']
						],
						'policy_title' => [
							'text' => $this->language->get('export_policy_title'),
							'value' => $result['policy_title']
						],
						'policy_description' => [
							'text' => $this->language->get('export_policy_description'),
							'value' => html_entity_decode($result['policy_description'], ENT_QUOTES, 'UTF-8')
						]
					];


					// create a file with name.json
					$file_name = 'policydata.json';
					$file_to_save = DIR_UPLOAD . $file_name;

					$handle = fopen($file_to_save, "w");

					fwrite($handle, json_encode($export_data, JSON_PRETTY_PRINT));
					fclose($handle);

				}

				if ($file_format == 'xml') {
					$export_data = [];

					$xml = new \DOMDocument('1.0', 'UTF-8');

					$xml->preserveWhiteSpace = false;
					$xml->formatOutput = true;

					$xml_policydata = $xml->createElement("policydata");
					$xml->appendChild($xml_policydata);

					$export_data = [];

					$export_data = [
						'policyacceptance_id' => [
							'text' => $this->language->get('export_policyacceptance_id'),
							'value' => $result['mpgdpr_policyacceptance_id']
						],
						'policy_id' => [
							'text' => $this->language->get('export_policy_id'),
							'value' => $result['policy_id']
						],
						'policy_title' => [
							'text' => $this->language->get('export_policy_title'),
							'value' => $result['policy_title']
						],
						'policy_description' => [
							'text' => $this->language->get('export_policy_description'),
							'value' => html_entity_decode($result['policy_description'], ENT_QUOTES, 'UTF-8')
						]
					];

					foreach ($export_data as $key => $edata) {
						if ($edata['value'] == '') {
							$edata['value'] = ' ';
						}

						$xml_edata = $xml->createElement($key, $edata['value']);
						// $xml_edata->setAttribute("text", $edata['text']);
						$xml_policydata->appendChild($xml_edata);

						// $xml_attr = $xml->createAttribute('text');
						// $xml_attr->value = $edata['text'];
						// $xml_edata->appendChild($xml_attr);
					}

					$file_name = 'policydata.xml';
					$file_to_save = DIR_UPLOAD . $file_name;

					// echo $xml->saveXML();
					$xml->save($file_to_save);
				}
				// 29 dec 2022 changes ends

				$json['redirect'] = str_replace('&amp;', '&', $this->url->link($this->extension_path . 'mpgdpr/policyacceptance/fileDownload', $this->token.'='.$this->session->data[$this->token].'&file_name='. $file_name .'&file_format='. $file_format, true));

			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function fileDownload() {
		$this->load->language($this->extension_path . 'mpgdpr/policyacceptance');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
		// 01-05-2022: updation start
		$file_to_save = DIR_UPLOAD . $this->request->get['file_name'];
		if ($this->user->isLogged() && file_exists($file_to_save)) {
			// header('Content-Type: application/vnd.ms-excel');
			header('Pragma: public');
			header('Expires: 0');
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment;filename="'. $this->request->get['file_name'] .'"');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: '. filesize($file_to_save));
			header('Cache-Control: max-age=0');
			header('Accept-Ranges: bytes');
			readfile($file_to_save);

			unlink($file_to_save);
		} else {
			$this->session->data['warning'] = $this->language->get('error_invalid');
			$this->response->redirect($this->url->link($this->extension_path . 'mpgdpr/policyacceptance', $this->token.'='.$this->session->data[$this->token], true));
		}
		// 01-05-2022: updation end
	}

	// 01-05-2022: updation start
	public function policyDelete() {
		$json = [];

		$this->load->language($this->extension_path . 'mpgdpr/policyacceptance');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

		if (!$this->validateDelete()) {
			$json['error'] = $this->error;
		}

		if (!$json && !empty($this->request->post['mpgdpr_policyacceptance_id'])) {
			$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->deletePolicyAcceptance($this->request->post['mpgdpr_policyacceptance_id']);
			$json['success'] = $this->language->get('text_success');
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	// 01-05-2022: updation end
}
