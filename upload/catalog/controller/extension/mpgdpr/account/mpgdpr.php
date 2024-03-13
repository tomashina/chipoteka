<?php

set_time_limit(0);
ini_set('memory_limit', '999M');
ini_set('set_time_limit', '0');

// Autoloader
require_once(DIR_SYSTEM . 'library/mpgdpr/composer/vendor/autoload.php');

// https://www.dropzonejs.com/#configuration

class ControllerExtensionMpGdprAccountMpGdpr extends \Mpgdpr\Controller {

	use \Mpgdpr\trait_mpgdpr_catalog;

	public function __construct($registry) {
		parent :: __construct($registry);
		$this->igniteTraitMpgdprCatalog($registry);
	}

	public function index() {
		if ($this->config->get('mpgdpr_login_gdprforms') && !$this->customer->getId()) {
			$this->session->data['redirect'] = $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}
		if (!$this->config->get('mpgdpr_status')) {
			return new Action('error/not_found');
		}
		$this->load->language($this->extension_path . 'mpgdpr/mpgdpr');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_gdpr'),
			'href' => $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr', '', true)
		];

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];

			unset($this->session->data['error']);
		} elseif (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_right_rectification'] = $this->language->get('text_right_rectification');
		$data['text_right_rectification_info'] = $this->language->get('text_right_rectification_info');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_address'] = $this->language->get('text_address');
		$data['text_password'] = $this->language->get('text_password');
		$data['text_newsletter'] = $this->language->get('text_newsletter');

		$data['text_right_portability'] = $this->language->get('text_right_portability');
		$data['text_right_portability_info'] = $this->language->get('text_right_portability_info');
		$data['text_port_personal_data'] = $this->language->get('text_port_personal_data');
		$data['text_port_address'] = $this->language->get('text_port_address');
		$data['text_port_orders'] = $this->language->get('text_port_orders');
		$data['text_my_gdpr_requests'] = $this->language->get('text_my_gdpr_requests');
		$data['text_my_wishlists'] = $this->language->get('text_my_wishlists');
		$data['text_my_transactions'] = $this->language->get('text_my_transactions');
		$data['text_my_history'] = $this->language->get('text_my_history');
		$data['text_my_search'] = $this->language->get('text_my_search');
		$data['text_my_rewardspoints'] = $this->language->get('text_my_rewardspoints');
		$data['text_my_activities'] = $this->language->get('text_my_activities');

		$data['text_right_restriction'] = $this->language->get('text_right_restriction');
		$data['text_right_restriction_info'] = $this->language->get('text_right_restriction_info');
		$data['text_my_restrictions'] = $this->language->get('text_my_restrictions');

		$data['text_right_personsal_data'] = $this->language->get('text_right_personsal_data');
		$data['text_right_personsal_data_info'] = $this->language->get('text_right_personsal_data_info');
		$data['text_personsal_data_request'] = $this->language->get('text_personsal_data_request');


		$data['text_right_forget_me'] = $this->language->get('text_right_forget_me');
		$data['text_right_forget_me_info'] = $this->language->get('text_right_forget_me_info');
		$data['text_forget_me'] = $this->language->get('text_forget_me');

		$data['button_back'] = $this->language->get('button_back');

		$data['account'] = $this->url->link('account/edit', '', true);
		$data['address'] = $this->url->link('account/address', '', true);
		$data['password'] = $this->url->link('account/password', '', true);
		$data['newsletter'] = $this->url->link('account/newsletter', '', true);

		$data['my_restrictions'] = $this->url->link($this->extension_path . 'mpgdpr/account/restriction', '', true);

		$data['data_request'] = $this->url->link($this->extension_path . 'mpgdpr/account/datarequest', '', true);
		$data['deleteme'] = $this->url->link($this->extension_path . 'mpgdpr/account/deleteme', '', true);

		$data['back'] = $this->url->link('account/account', '', true);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		/*here we can override to search histories, in case when in past admin enable log customer search, but now disable that, so on demand we need to show download search histories*/
		$data['customer_search'] = false;// $this->config->get('config_customer_search');

		$data['extension_path'] = $this->extension_path;

		$this->response->setOutput($this->viewLoad($this->extension_path . 'mpgdpr/account/mpgdpr', $data));
	}

	public function fileDownload() {
		$this->load->language($this->extension_path . 'mpgdpr/mpgdpr');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		/*13 sep 2019 gdpr session starts*/
		if ($this->customer->getId() && !empty($this->request->get['file_name'])) {
		/*13 sep 2019 gdpr session ends*/
			$file_to_save = DIR_UPLOAD . $this->request->get['file_name'];
			if (!file_exists($file_to_save)) {
				$this->session->data['warning'] = $this->language->get('error_file_exists');
				$this->response->redirect($this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr', '', true));
			}
			// 01-05-2022: updation start
			header('Pragma: public');
			header('Expires: 0');
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			// header('Content-Type: application/vnd.ms-excel');
			// header('Content-Type: application/json');
			// header('Content-Type: application/xml');

			header('Content-Disposition: attachment;filename="'. $this->request->get['file_name'] .'"');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: '. filesize($file_to_save));
			header('Cache-Control: max-age=0');
			header('Accept-Ranges: bytes');
			readfile($file_to_save);

			unlink($file_to_save);
			// 01-05-2022: updation end
		} else {
			$this->session->data['warning'] = $this->language->get('error_login_required');
			$this->response->redirect($this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr', '', true));
		}
	}
	public function getAccountData() {
		// download account data as csv
		$this->response->addHeader('Content-Type: application/json');
		$json = [];
		if (!$this->config->get('mpgdpr_status')) {
			$this->response->setOutput(json_encode([]));
			$this->response->output();
			exit;
		}
		$this->load->language($this->extension_path . 'mpgdpr/mpgdpr');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');

		if (!$this->customer->getId()) {
			$json['warning'] =  $this->language->get('error_login_required');
		}

		if (!$json) {
			// process all data
			$file_format = 'csv';
			$file_name = 'personaldata_request.csv';
			if (in_array($this->config->get('mpgdpr_export_format'), ['csv','xls','xlsx','json','xml'])) {
				$file_format = $this->config->get('mpgdpr_export_format');
			}

			$customer = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerData($this->customer->getId());

			if (in_array($file_format, ['csv','xls','xlsx'])) {

				$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
				$i = 1;
				$char = 'A';

				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customer_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_firstname'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_lastname'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_telephone'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_fax'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_email'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_newsletter'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_ip'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_date_added'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);

				// Background Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('1A017FBE');
				// Font Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF');

				// process all data

				if ($customer) {
					$char_value = 'A'; $i++;

					if ($customer['newsletter']) {
						$newsletter = $this->language->get('text_yes');
					} else {
						$newsletter = $this->language->get('text_no');
					}

					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $customer['customer_id']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $customer['firstname']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $customer['lastname']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $customer['telephone']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $customer['fax']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $customer['email']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $newsletter);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $customer['ip']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $customer['date_added']);
				}

				// Find Format
				if ($file_format == 'xls') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($objPHPExcel);
					$file_name = 'personaldata_request.xls';
				} elseif ($file_format == 'xlsx') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'personaldata_request.xlsx';
				} elseif ($file_format == 'csv') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($objPHPExcel);
					$file_name = 'personaldata_request.csv';
				} else {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'personaldata_request.xlsx';
				}

				$file_to_save = DIR_UPLOAD . $file_name;
				$objWriter->save(DIR_UPLOAD . $file_name);
				// $objWriter->save('php://stdout');
				// $objWriter->save('php://output');
			}

			if ($file_format == 'json') {
				$export_data = [];
				// process all data
				if ($customer) {
					if ($customer['newsletter']) {
						$newsletter = $this->language->get('text_yes');
					} else {
						$newsletter = $this->language->get('text_no');
					}

					$export_data = [
						'customer_id' => [
							'text' => $this->language->get('export_customer_id'),
							'value' => $customer['customer_id']
						],
						'firstname' => [
							'text' => $this->language->get('export_firstname'),
							'value' => $customer['firstname']
						],
						'lastname' => [
							'text' => $this->language->get('export_lastname'),
							'value' => $customer['lastname']
						],
						'telephone' => [
							'text' => $this->language->get('export_telephone'),
							'value' => $customer['telephone']
						],
						'fax' => [
							'text' => $this->language->get('export_fax'),
							'value' => $customer['fax']
						],
						'email' => [
							'text' => $this->language->get('export_email'),
							'value' => $customer['email']
						],
						'newsletter' => [
							'text' => $this->language->get('export_newsletter'),
							'value' => $newsletter
						],
						'ip' => [
							'text' => $this->language->get('export_ip'),
							'value' => $customer['ip']
						],
						'date_added' => [
							'text' => $this->language->get('export_date_added'),
							'value' => $customer['date_added']
						],
					];
				}
				// create a file with name.json
				$file_name = 'personaldata_request.json';
				$file_to_save = DIR_UPLOAD . $file_name;

				$handle = fopen($file_to_save, "w");

				fwrite($handle, json_encode($export_data, JSON_PRETTY_PRINT));
				fclose($handle);
			}

			if ($file_format == 'xml') {
				$export_data = [];
				// process all data
				if ($customer) {
					if ($customer['newsletter']) {
						$newsletter = $this->language->get('text_yes');
					} else {
						$newsletter = $this->language->get('text_no');
					}

			    	$export_data = [
						'customer_id' => [
							'text' => $this->language->get('export_customer_id'),
							'value' => $customer['customer_id']
						],
						'firstname' => [
							'text' => $this->language->get('export_firstname'),
							'value' => $customer['firstname']
						],
						'lastname' => [
							'text' => $this->language->get('export_lastname'),
							'value' => $customer['lastname']
						],
						'telephone' => [
							'text' => $this->language->get('export_telephone'),
							'value' => $customer['telephone']
						],
						'fax' => [
							'text' => $this->language->get('export_fax'),
							'value' => $customer['fax']
						],
						'email' => [
							'text' => $this->language->get('export_email'),
							'value' => $customer['email']
						],
						'newsletter' => [
							'text' => $this->language->get('export_newsletter'),
							'value' => $newsletter
						],
						'ip' => [
							'text' => $this->language->get('export_ip'),
							'value' => $customer['ip']
						],
						'date_added' => [
							'text' => $this->language->get('export_date_added'),
							'value' => $customer['date_added']
						],
					];
				}

				$xml = new \DOMDocument('1.0', 'UTF-8');

		   		$xml->preserveWhiteSpace = false;
				$xml->formatOutput = true;

				$xml_customer = $xml->createElement("customer");
				$xml->appendChild($xml_customer);

				foreach ($export_data as $key => $edata) {
					if ($edata['value'] == '') {
						$edata['value'] = ' ';
					}
					$xml_edata = $xml->createElement($key, $edata['value']);
					// $xml_edata->setAttribute("text", $edata['text']);
					$xml_customer->appendChild($xml_edata);

					// $xml_attr = $xml->createAttribute('text');
					// $xml_attr->value = $edata['text'];
					// $xml_edata->appendChild($xml_attr);

				}

				$file_name = 'personaldata_request.xml';
				$file_to_save = DIR_UPLOAD . $file_name;

				// echo $xml->saveXML();
				$xml->save($file_to_save);

			}

			// record download personal data activity and Add to request log

			$request_data = [
				'customer_id' => $this->customer->getId(),
			];
			// 01-05-2022: updation start
			$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEDOWNLOADPERSONALINFO, $request_data);
			// 01-05-2022: updation end
			$json['redirect'] = str_replace('&amp;', '&', $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr/fileDownload', 'file_name='. $file_name .'&file_format='. $file_format, true));
		}
		$this->response->setOutput(json_encode($json));
	}
	public function getAddresses() {
		// download addresses data as csv
		$this->response->addHeader('Content-Type: application/json');
		$json = [];
		if (!$this->config->get('mpgdpr_status')) {
			$this->response->setOutput(json_encode([]));
			$this->response->output();
			exit;
		}
		$this->load->language($this->extension_path . 'mpgdpr/mpgdpr');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
		$this->load->model('localisation/country');
		$this->load->model('localisation/zone');
		if (!$this->customer->getId()) {
			$json['warning'] =  $this->language->get('error_login_required');
		}

		if (!$json) {
			// process all data
			$file_format = 'csv';
			$file_name = 'customer_addresses.csv';
			if (in_array($this->config->get('mpgdpr_export_format'), ['csv','xls','xlsx','json','xml'])) {
				$file_format = $this->config->get('mpgdpr_export_format');
			}

			$addresses = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerAddresses($this->customer->getId());

			if (in_array($file_format, ['csv','xls','xlsx'])) {


				$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

				$i = 1;
				$char = 'A';

				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_address_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customer_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_address_firstname'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_address_lastname'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_address_company'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_address_address1'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_address_address2'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_address_city'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_address_postalcode'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_address_country'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_address_zone'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_address_customfield'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);

				// Background Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('1A017FBE');
				// Font Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF');

				// process all data

				foreach ($addresses as $address) {
					$char_value = 'A'; $i++;

					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $address['address_id']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $address['customer_id']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $address['firstname']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $address['lastname']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $address['company']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $address['address_1']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $address['address_2']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $address['city']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $address['postcode']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $address['country']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $address['zone']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $address['custom_field']);
				}

				// Find Format
				if ($file_format == 'xls') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($objPHPExcel);
					$file_name = 'customer_addresses.xls';
				} elseif ($file_format == 'xlsx') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_addresses.xlsx';
				} elseif ($file_format == 'csv') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($objPHPExcel);
					$file_name = 'customer_addresses.csv';
				} else {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_addresses.xlsx';
				}

				$file_to_save = DIR_UPLOAD . $file_name;
				$objWriter->save(DIR_UPLOAD . $file_name);
			}

			if ($file_format == 'json') {
				$export_data = [];
				// process all data
				foreach ($addresses as $address) {
					$export_data[] = [
						'address_id' => [
							'text' => $this->language->get('export_address_id'),
							'value' => $address['address_id']
						],
						'customer_id' => [
							'text' => $this->language->get('export_customer_id'),
							'value' => $address['customer_id']
						],
						'firstname' => [
							'text' => $this->language->get('export_address_firstname'),
							'value' => $address['firstname']
						],
						'lastname' => [
							'text' => $this->language->get('export_address_lastname'),
							'value' => $address['lastname']
						],
						'company' => [
							'text' => $this->language->get('export_address_company'),
							'value' => $address['company']
						],
						'address_1' => [
							'text' => $this->language->get('export_address_address1'),
							'value' => $address['address_1']
						],
						'address_2' => [
							'text' => $this->language->get('export_address_address2'),
							'value' => $address['address_2']
						],
						'city' => [
							'text' => $this->language->get('export_address_city'),
							'value' => $address['city']
						],
						'postcode' => [
							'text' => $this->language->get('export_address_postalcode'),
							'value' => $address['postcode']
						],
						'country' => [
							'text' => $this->language->get('export_address_country'),
							'value' => $address['country']
						],
						'zone' => [
							'text' => $this->language->get('export_address_zone'),
							'value' => $address['zone']
						],
						'custom_field' => [
							'text' => $this->language->get('export_address_customfield'),
							'value' => $address['custom_field']
						],
					];
				}

				// create a file with name.json
				$file_name = 'customer_addresses.json';
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

				$xml_addresses = $xml->createElement("addresses");
				$xml->appendChild($xml_addresses);
				// process all data
				foreach ($addresses as $address) {
					$xml_address = $xml->createElement("address");
					$xml_addresses->appendChild($xml_address);

					$export_data = [];

					$export_data = [
						'address_id' => [
							'text' => $this->language->get('export_address_id'),
							'value' => $address['address_id']
						],
						'customer_id' => [
							'text' => $this->language->get('export_customer_id'),
							'value' => $address['customer_id']
						],
						'firstname' => [
							'text' => $this->language->get('export_address_firstname'),
							'value' => $address['firstname']
						],
						'lastname' => [
							'text' => $this->language->get('export_address_lastname'),
							'value' => $address['lastname']
						],
						'company' => [
							'text' => $this->language->get('export_address_company'),
							'value' => $address['company']
						],
						'address_1' => [
							'text' => $this->language->get('export_address_address1'),
							'value' => $address['address_1']
						],
						'address_2' => [
							'text' => $this->language->get('export_address_address2'),
							'value' => $address['address_2']
						],
						'city' => [
							'text' => $this->language->get('export_address_city'),
							'value' => $address['city']
						],
						'postcode' => [
							'text' => $this->language->get('export_address_postalcode'),
							'value' => $address['postcode']
						],
						'country' => [
							'text' => $this->language->get('export_address_country'),
							'value' => $address['country']
						],
						'zone' => [
							'text' => $this->language->get('export_address_zone'),
							'value' => $address['zone']
						],
						'custom_field' => [
							'text' => $this->language->get('export_address_customfield'),
							'value' => $address['custom_field']
						],
					];
					foreach ($export_data as $key => $edata) {
						if ($edata['value'] == '') {
							$edata['value'] = ' ';
						}
						// if ($key == 'custom_field') {
						// 	continue;
						// }

						$xml_edata = $xml->createElement($key, $edata['value']);
						// $xml_edata->setAttribute("text", $edata['text']);
						$xml_address->appendChild($xml_edata);

						// $xml_attr = $xml->createAttribute('text');
						// $xml_attr->value = $edata['text'];
						// $xml_edata->appendChild($xml_attr);
					}
				}

				$file_name = 'customer_addresses.xml';
				$file_to_save = DIR_UPLOAD . $file_name;

				// echo $xml->saveXML();
				$xml->save($file_to_save);

			}

			// record download addresses activity and Add to request log

			$request_data = [
				'customer_id' => $this->customer->getId(),
			];
			// 01-05-2022: updation start
			$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEDOWNLOADADDRESS, $request_data);
			// 01-05-2022: updation end
			$json['redirect'] = str_replace('&amp;', '&', $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr/fileDownload', 'file_name='. $file_name .'&file_format='. $file_format, true));
		}

		$this->response->setOutput(json_encode($json));
	}
	public function getOrders() {
		// download orders data as csv
		$this->response->addHeader('Content-Type: application/json');
		$json = [];
		if (!$this->config->get('mpgdpr_status')) {
			$this->response->setOutput(json_encode([]));
			$this->response->output();
			exit;
		}
		$this->load->language($this->extension_path . 'mpgdpr/mpgdpr');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
		$this->load->model('localisation/country');
		$this->load->model('localisation/zone');
		$this->load->model('localisation/language');
		if (!$this->customer->getId()) {
			$json['warning'] =  $this->language->get('error_login_required');
		}

		if (!$json) {
			// process all data
			$file_format = 'csv';
			$file_name = 'customer_order.csv';
			if (in_array($this->config->get('mpgdpr_export_format'), ['csv','xls','xlsx','json','xml'])) {
				$file_format = $this->config->get('mpgdpr_export_format');
			}

			$orders = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerOrders($this->customer->getId(), (int)$this->config->get('config_language_id'));

			if (in_array($file_format, ['csv','xls','xlsx'])) {

				$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

				$i = 1;
				$char = 'A';

				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customer_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_inv'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_inv_prefix'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_firstname'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_lastname'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_email'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_telephone'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_fax'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_customfield'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_payment_firstname'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_payment_lastname'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_payment_company'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_payment_address1'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_payment_address2'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_payment_city'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_payment_postcode'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_payment_country'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_payment_zone'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_payment_customfield'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_payment_method'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_shipping_firstname'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_shipping_lastname'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_shipping_company'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_shipping_address1'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_shipping_address2'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_shipping_city'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_shipping_postcode'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_shipping_country'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_shipping_zone'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_shipping_customfield'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_shipping_method'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_comment'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_othertotal'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_total'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_ip'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_forwaredip'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_useragent'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_acceptlanguage'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_date_added'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_date_modified'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_history'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_product'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_voucher'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);

				// Background Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('1A017FBE');
				// Font Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF');

				// process all data
				foreach ($orders as $order) {
					$char_value = 'A'; $i++;

					$payment_country = $this->model_localisation_country->getCountry($order['payment_country_id']);

					if ($payment_country) {
						$payment_iso_code_2 = $payment_country['iso_code_2'];
						$payment_iso_code_3 = $payment_country['iso_code_3'];
					} else {
						$payment_iso_code_2 = '';
						$payment_iso_code_3 = '';
					}

					$payment_zone = $this->model_localisation_zone->getZone($order['payment_zone_id']);

					if ($payment_zone) {
						$payment_zone_code = $payment_zone['code'];
					} else {
						$payment_zone_code = '';
					}

					$shipping_country = $this->model_localisation_country->getCountry($order['shipping_country_id']);

					if ($shipping_country) {
						$shipping_iso_code_2 = $shipping_country['iso_code_2'];
						$shipping_iso_code_3 = $shipping_country['iso_code_3'];
					} else {
						$shipping_iso_code_2 = '';
						$shipping_iso_code_3 = '';
					}

					$shipping_zone = $this->model_localisation_zone->getZone($order['shipping_zone_id']);

					if ($shipping_zone) {
						$shipping_zone_code = $shipping_zone['code'];
					} else {
						$shipping_zone_code = '';
					}

					// order has missing language then use default language
					if (!$order['language_id']) {
						$order['language_id'] = $this->config->get('config_language_id');
					}

					$language_info = $this->model_localisation_language->getLanguage($order['language_id']);

					if ($language_info) {
						$language_code = $language_info['code'];
					} else {
						$language_code = $this->config->get('config_language');
					}

					$language = new Language($language_code);
					$language->load($language_code);

					// order other totals
					$order_total = [];
					$ordertotals = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderTotals($order['order_id']);
					foreach ($ordertotals as $value) {
						$order_total[][$value['code']] = $this->currency->format($value['value'], $order['currency_code'], $order['currency_value']);
					}
					$order_othertotal = json_encode($order_total);

					// order histories
					$history = [];
					$orderhistories = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderHistories($order['order_id'], $order['language_id']);
					foreach ($orderhistories as $value) {
						$history[] = [
							'order_status' => $value['status'],
							'notify' => $value['notify'] ? $language->get('text_yes') : $language->get('text_no'),
							'comment' => $value['comment'],
							'date_added' => $value['date_added']
						];
					}
					$order_history = json_encode($history);

					// order products
					$product = [];
					$orderproducts = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderProducts($order['order_id']);

					foreach ($orderproducts as $value) {
						$options = [];

						$orderproduct_options = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderOptions($order['order_id'], $value['order_product_id']);

						foreach ($orderproduct_options as $option) {
							$options[] = [
								'name' => $option['name'],
								'value' => $option['value']
							];
						}

						$product[] = [
							'product_id' => $value['product_id'],
							'name' => $value['name'],
							'model' => $value['model'],
							'quantity' => $value['quantity'],
							'price' => $this->currency->format($value['price'] + ($this->config->get('config_tax') ? $value['tax'] : 0), $order['currency_code'], $order['currency_value']),
							'total' => $this->currency->format($value['total'] + ($this->config->get('config_tax') ? ($value['tax'] * $value['quantity']) : 0), $order['currency_code'], $order['currency_value']),
							'reward' => $value['reward'],
							'options' => $options
						];
					}

					$order_product = json_encode($product);

					// order vouchers
					$voucher = [];
					$ordervouchers = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderVouchers($order['order_id']);

					foreach ($ordervouchers as $value) {
						$vouchertheme = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getVoucherTheme($value['voucher_id'], $order['language_id']);
						$theme = '';
						if ($vouchertheme) {
							$theme = $vouchertheme['name'];
						}
						$voucher[] = [
							'voucher_id' => $value['voucher_id'],
							'description' => $value['description'],
							'code' => $value['code'],
							'from_name' => $value['from_name'],
							'from_email' => $value['from_email'],
							'to_name' => $value['to_name'],
							'to_email' => $value['to_email'],
							'theme' => $theme,
							'message' => $value['message'],
							'amount' => $this->currency->format($value['amount'], $order['currency_code'], $order['currency_value'])
						];
					}

					$order_voucher = json_encode($voucher);

					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['order_id']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['customer_id']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['invoice_no']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['invoice_prefix']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['firstname']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['lastname']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['email']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['telephone']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['fax']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['custom_field']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['payment_firstname']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['payment_lastname']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['payment_company']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['payment_address_1']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['payment_address_2']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['payment_city']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['payment_postcode']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['payment_country']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['payment_zone']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['payment_custom_field']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['payment_method']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['shipping_firstname']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['shipping_lastname']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['shipping_company']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['shipping_address_1']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['shipping_address_2']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['shipping_city']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['shipping_postcode']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['shipping_country']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['shipping_zone']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['shipping_custom_field']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['shipping_method']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['comment']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order_othertotal);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $this->currency->format($order['total'], $order['currency_code'], $order['currency_value']));
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['ip']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['forwarded_ip']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['user_agent']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['accept_language']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['date_added']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order['date_modified']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order_history);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order_product);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $order_voucher);
				}

				// Find Format
				if ($file_format == 'xls') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($objPHPExcel);
					$file_name = 'customer_order.xls';
				} elseif ($file_format == 'xlsx') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_order.xlsx';
				} elseif ($file_format == 'csv') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($objPHPExcel);
					$file_name = 'customer_order.csv';
				} else {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_order.xlsx';
				}

				$file_to_save = DIR_UPLOAD . $file_name;
				$objWriter->save(DIR_UPLOAD . $file_name);
			}

			if ($file_format == 'json') {
				$export_data = [];


				// process all data
				foreach ($orders as $order) {

					$payment_country = $this->model_localisation_country->getCountry($order['payment_country_id']);

					if ($payment_country) {
						$payment_iso_code_2 = $payment_country['iso_code_2'];
						$payment_iso_code_3 = $payment_country['iso_code_3'];
					} else {
						$payment_iso_code_2 = '';
						$payment_iso_code_3 = '';
					}

					$payment_zone = $this->model_localisation_zone->getZone($order['payment_zone_id']);

					if ($payment_zone) {
						$payment_zone_code = $payment_zone['code'];
					} else {
						$payment_zone_code = '';
					}

					$shipping_country = $this->model_localisation_country->getCountry($order['shipping_country_id']);

					if ($shipping_country) {
						$shipping_iso_code_2 = $shipping_country['iso_code_2'];
						$shipping_iso_code_3 = $shipping_country['iso_code_3'];
					} else {
						$shipping_iso_code_2 = '';
						$shipping_iso_code_3 = '';
					}

					$shipping_zone = $this->model_localisation_zone->getZone($order['shipping_zone_id']);

					if ($shipping_zone) {
						$shipping_zone_code = $shipping_zone['code'];
					} else {
						$shipping_zone_code = '';
					}

					// order has missing language then use default language
					if (!$order['language_id']) {
						$order['language_id'] = $this->config->get('config_language_id');
					}

					$language_info = $this->model_localisation_language->getLanguage($order['language_id']);

					if ($language_info) {
						$language_code = $language_info['code'];
					} else {
						$language_code = $this->config->get('config_language');
					}

					$language = new Language($language_code);
					$language->load($language_code);

					// order other totals
					$order_total = [];
					$ordertotals = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderTotals($order['order_id']);
					foreach ($ordertotals as $value) {
						$order_total[][$value['code']] = $this->currency->format($value['value'], $order['currency_code'], $order['currency_value']);
					}
					$order_othertotal = json_encode($order_total);

					// order histories
					$history = [];
					$orderhistories = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderHistories($order['order_id'], $order['language_id']);
					foreach ($orderhistories as $value) {
						$history[] = [
							'order_status' => $value['status'],
							'notify' => $value['notify'] ? $language->get('text_yes') : $language->get('text_no'),
							'comment' => $value['comment'],
							'date_added' => $value['date_added']
						];
					}
					$order_history = json_encode($history);

					// order products
					$product = [];
					$orderproducts = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderProducts($order['order_id']);

					foreach ($orderproducts as $value) {
						$options = [];

						$orderproduct_options = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderOptions($order['order_id'], $value['order_product_id']);

						foreach ($orderproduct_options as $option) {
							$options[] = [
								'name' => $option['name'],
								'value' => $option['value']
							];
						}

						$product[] = [
							'product_id' => $value['product_id'],
							'name' => $value['name'],
							'model' => $value['model'],
							'quantity' => $value['quantity'],
							'price' => $this->currency->format($value['price'] + ($this->config->get('config_tax') ? $value['tax'] : 0), $order['currency_code'], $order['currency_value']),
							'total' => $this->currency->format($value['total'] + ($this->config->get('config_tax') ? ($value['tax'] * $value['quantity']) : 0), $order['currency_code'], $order['currency_value']),
							'reward' => $value['reward'],
							'options' => $options
						];
					}

					$order_product = json_encode($product);

					// order vouchers
					$voucher = [];
					$ordervouchers = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderVouchers($order['order_id']);

					foreach ($ordervouchers as $value) {
						$vouchertheme = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getVoucherTheme($value['voucher_id'], $order['language_id']);
						$theme = '';
						if ($vouchertheme) {
							$theme = $vouchertheme['name'];
						}
						$voucher[] = [
							'voucher_id' => $value['voucher_id'],
							'description' => $value['description'],
							'code' => $value['code'],
							'from_name' => $value['from_name'],
							'from_email' => $value['from_email'],
							'to_name' => $value['to_name'],
							'to_email' => $value['to_email'],
							'theme' => $theme,
							'message' => $value['message'],
							'amount' => $this->currency->format($value['amount'], $order['currency_code'], $order['currency_value'])
						];
					}

					$order_voucher = json_encode($voucher);

					$export_data[] = [
						'order_id' => [
							'text' => $this->language->get('export_order_id'),
							'value' => $order['order_id']
						],
						'customer_id' => [
							'text' => $this->language->get('export_customer_id'),
							'value' => $order['customer_id']
						],
						'invoice_no' => [
							'text' => $this->language->get('export_order_inv'),
							'value' => $order['invoice_no']
						],
						'invoice_prefix' => [
							'text' => $this->language->get('export_order_inv_prefix'),
							'value' => $order['invoice_prefix']
						],
						'firstname' => [
							'text' => $this->language->get('export_order_firstname'),
							'value' => $order['firstname']
						],
						'lastname' => [
							'text' => $this->language->get('export_order_lastname'),
							'value' => $order['lastname']
						],
						'email' => [
							'text' => $this->language->get('export_order_email'),
							'value' => $order['email']
						],
						'telephone' => [
							'text' => $this->language->get('export_order_telephone'),
							'value' => $order['telephone']
						],
						'fax' => [
							'text' => $this->language->get('export_order_fax'),
							'value' => $order['fax']
						],
						'custom_field' => [
							'text' => $this->language->get('export_order_customfield'),
							'value' => $order['custom_field']
						],
						'payment_firstname' => [
							'text' => $this->language->get('export_order_payment_firstname'),
							'value' => $order['payment_firstname']
						],
						'payment_lastname' => [
							'text' => $this->language->get('export_order_payment_lastname'),
							'value' => $order['payment_lastname']
						],
						'payment_company' => [
							'text' => $this->language->get('export_order_payment_company'),
							'value' => $order['payment_company']
						],
						'payment_address_1' => [
							'text' => $this->language->get('export_order_payment_address1'),
							'value' => $order['payment_address_1']
						],
						'payment_address_2' => [
							'text' => $this->language->get('export_order_payment_address2'),
							'value' => $order['payment_address_2']
						],
						'payment_city' => [
							'text' => $this->language->get('export_order_payment_city'),
							'value' => $order['payment_city']
						],
						'payment_postcode' => [
							'text' => $this->language->get('export_order_payment_postcode'),
							'value' => $order['payment_postcode']
						],
						'payment_country' => [
							'text' => $this->language->get('export_order_payment_country'),
							'value' => $order['payment_country']
						],
						'payment_zone' => [
							'text' => $this->language->get('export_order_payment_zone'),
							'value' => $order['payment_zone']
						],
						'payment_custom_field' => [
							'text' => $this->language->get('export_order_payment_customfield'),
							'value' => $order['payment_custom_field']
						],
						'payment_method' => [
							'text' => $this->language->get('export_order_payment_method'),
							'value' => $order['payment_method']
						],
						'shipping_firstname' => [
							'text' => $this->language->get('export_order_shipping_firstname'),
							'value' => $order['shipping_firstname']
						],
						'shipping_lastname' => [
							'text' => $this->language->get('export_order_shipping_lastname'),
							'value' => $order['shipping_lastname']
						],
						'shipping_company' => [
							'text' => $this->language->get('export_order_shipping_company'),
							'value' => $order['shipping_company']
						],
						'shipping_address_1' => [
							'text' => $this->language->get('export_order_shipping_address1'),
							'value' => $order['shipping_address_1']
						],
						'shipping_address_2' => [
							'text' => $this->language->get('export_order_shipping_address2'),
							'value' => $order['shipping_address_2']
						],
						'shipping_city' => [
							'text' => $this->language->get('export_order_shipping_city'),
							'value' => $order['shipping_city']
						],
						'shipping_postcode' => [
							'text' => $this->language->get('export_order_shipping_postcode'),
							'value' => $order['shipping_postcode']
						],
						'shipping_country' => [
							'text' => $this->language->get('export_order_shipping_country'),
							'value' => $order['shipping_country']
						],
						'shipping_zone' => [
							'text' => $this->language->get('export_order_shipping_zone'),
							'value' => $order['shipping_zone']
						],
						'shipping_custom_field' => [
							'text' => $this->language->get('export_order_shipping_customfield'),
							'value' => $order['shipping_custom_field']
						],
						'shipping_method' => [
							'text' => $this->language->get('export_order_shipping_method'),
							'value' => $order['shipping_method']
						],
						'comment' => [
							'text' => $this->language->get('export_order_comment'),
							'value' => $order['comment']
						],
						'order_othertotal' => [
							'text' => $this->language->get('export_order_othertotal'),
							'value' => $order_othertotal
						],
						'total' => [
							'text' => $this->language->get('export_order_total'),
							'value' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'])
						],
						'ip' => [
							'text' => $this->language->get('export_order_ip'),
							'value' => $order['ip']
						],
						'forwarded_ip' => [
							'text' => $this->language->get('export_order_forwaredip'),
							'value' => $order['forwarded_ip']
						],
						'user_agent' => [
							'text' => $this->language->get('export_order_useragent'),
							'value' => $order['user_agent']
						],
						'accept_language' => [
							'text' => $this->language->get('export_order_acceptlanguage'),
							'value' => $order['accept_language']
						],
						'date_added' => [
							'text' => $this->language->get('export_order_date_added'),
							'value' => $order['date_added']
						],
						'date_modified' => [
							'text' => $this->language->get('export_order_date_modified'),
							'value' => $order['date_modified']
						],
						'order_history' => [
							'text' => $this->language->get('export_order_history'),
							'value' => $order_history
						],
						'order_product' => [
							'text' => $this->language->get('export_order_product'),
							'value' => $order_product
						],
						'order_voucher' => [
							'text' => $this->language->get('export_order_voucher'),
							'value' => $order_voucher
						],
					];

				}


				// create a file with name.json
				$file_name = 'customer_order.json';
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

				$xml_orders = $xml->createElement("orders");
				$xml->appendChild($xml_orders);

				// process all data
				foreach ($orders as $order) {

					$payment_country = $this->model_localisation_country->getCountry($order['payment_country_id']);

					if ($payment_country) {
						$payment_iso_code_2 = $payment_country['iso_code_2'];
						$payment_iso_code_3 = $payment_country['iso_code_3'];
					} else {
						$payment_iso_code_2 = '';
						$payment_iso_code_3 = '';
					}

					$payment_zone = $this->model_localisation_zone->getZone($order['payment_zone_id']);

					if ($payment_zone) {
						$payment_zone_code = $payment_zone['code'];
					} else {
						$payment_zone_code = '';
					}

					$shipping_country = $this->model_localisation_country->getCountry($order['shipping_country_id']);

					if ($shipping_country) {
						$shipping_iso_code_2 = $shipping_country['iso_code_2'];
						$shipping_iso_code_3 = $shipping_country['iso_code_3'];
					} else {
						$shipping_iso_code_2 = '';
						$shipping_iso_code_3 = '';
					}

					$shipping_zone = $this->model_localisation_zone->getZone($order['shipping_zone_id']);

					if ($shipping_zone) {
						$shipping_zone_code = $shipping_zone['code'];
					} else {
						$shipping_zone_code = '';
					}

					// order has missing language then use default language
					if (!$order['language_id']) {
						$order['language_id'] = $this->config->get('config_language_id');
					}

					$language_info = $this->model_localisation_language->getLanguage($order['language_id']);

					if ($language_info) {
						$language_code = $language_info['code'];
					} else {
						$language_code = $this->config->get('config_language');
					}

					$language = new Language($language_code);
					$language->load($language_code);

					// order other totals
					$order_total = [];
					$ordertotals = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderTotals($order['order_id']);
					foreach ($ordertotals as $value) {
						$order_total[][$value['code']] = $this->currency->format($value['value'], $order['currency_code'], $order['currency_value']);
					}
					$order_othertotal = json_encode($order_total);

					// order histories
					$history = [];
					$orderhistories = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderHistories($order['order_id'], $order['language_id']);
					foreach ($orderhistories as $value) {
						$history[] = [
							'order_status' => $value['status'],
							'notify' => $value['notify'] ? $language->get('text_yes') : $language->get('text_no'),
							'comment' => $value['comment'],
							'date_added' => $value['date_added']
						];
					}
					$order_history = json_encode($history);

					// order products
					$product = [];
					$orderproducts = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderProducts($order['order_id']);

					foreach ($orderproducts as $value) {
						$options = [];

						$orderproduct_options = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderOptions($order['order_id'], $value['order_product_id']);

						foreach ($orderproduct_options as $option) {
							$options[] = [
								'name' => $option['name'],
								'value' => $option['value']
							];
						}

						$product[] = [
							'product_id' => $value['product_id'],
							'name' => $value['name'],
							'model' => $value['model'],
							'quantity' => $value['quantity'],
							'price' => $this->currency->format($value['price'] + ($this->config->get('config_tax') ? $value['tax'] : 0), $order['currency_code'], $order['currency_value']),
							'total' => $this->currency->format($value['total'] + ($this->config->get('config_tax') ? ($value['tax'] * $value['quantity']) : 0), $order['currency_code'], $order['currency_value']),
							'reward' => $value['reward'],
							'options' => $options
						];
					}

					$order_product = json_encode($product);

					// order vouchers
					$voucher = [];
					$ordervouchers = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getOrderVouchers($order['order_id']);

					foreach ($ordervouchers as $value) {
						$vouchertheme = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getVoucherTheme($value['voucher_id'], $order['language_id']);
						$theme = '';
						if ($vouchertheme) {
							$theme = $vouchertheme['name'];
						}
						$voucher[] = [
							'voucher_id' => $value['voucher_id'],
							'description' => $value['description'],
							'code' => $value['code'],
							'from_name' => $value['from_name'],
							'from_email' => $value['from_email'],
							'to_name' => $value['to_name'],
							'to_email' => $value['to_email'],
							'theme' => $theme,
							'message' => $value['message'],
							'amount' => $this->currency->format($value['amount'], $order['currency_code'], $order['currency_value'])
						];
					}

					$order_voucher = json_encode($voucher);

					$export_data = [];

					$export_data = [
						'order_id' => [
							'text' => $this->language->get('export_order_id'),
							'value' => $order['order_id']
						],
						'customer_id' => [
							'text' => $this->language->get('export_customer_id'),
							'value' => $order['customer_id']
						],
						'invoice_no' => [
							'text' => $this->language->get('export_order_inv'),
							'value' => $order['invoice_no']
						],
						'invoice_prefix' => [
							'text' => $this->language->get('export_order_inv_prefix'),
							'value' => $order['invoice_prefix']
						],
						'firstname' => [
							'text' => $this->language->get('export_order_firstname'),
							'value' => $order['firstname']
						],
						'lastname' => [
							'text' => $this->language->get('export_order_lastname'),
							'value' => $order['lastname']
						],
						'email' => [
							'text' => $this->language->get('export_order_email'),
							'value' => $order['email']
						],
						'telephone' => [
							'text' => $this->language->get('export_order_telephone'),
							'value' => $order['telephone']
						],
						'fax' => [
							'text' => $this->language->get('export_order_fax'),
							'value' => $order['fax']
						],
						'custom_field' => [
							'text' => $this->language->get('export_order_customfield'),
							'value' => $order['custom_field']
						],
						'payment_firstname' => [
							'text' => $this->language->get('export_order_payment_firstname'),
							'value' => $order['payment_firstname']
						],
						'payment_lastname' => [
							'text' => $this->language->get('export_order_payment_lastname'),
							'value' => $order['payment_lastname']
						],
						'payment_company' => [
							'text' => $this->language->get('export_order_payment_company'),
							'value' => $order['payment_company']
						],
						'payment_address_1' => [
							'text' => $this->language->get('export_order_payment_address1'),
							'value' => $order['payment_address_1']
						],
						'payment_address_2' => [
							'text' => $this->language->get('export_order_payment_address2'),
							'value' => $order['payment_address_2']
						],
						'payment_city' => [
							'text' => $this->language->get('export_order_payment_city'),
							'value' => $order['payment_city']
						],
						'payment_postcode' => [
							'text' => $this->language->get('export_order_payment_postcode'),
							'value' => $order['payment_postcode']
						],
						'payment_country' => [
							'text' => $this->language->get('export_order_payment_country'),
							'value' => $order['payment_country']
						],
						'payment_zone' => [
							'text' => $this->language->get('export_order_payment_zone'),
							'value' => $order['payment_zone']
						],
						'payment_custom_field' => [
							'text' => $this->language->get('export_order_payment_customfield'),
							'value' => $order['payment_custom_field']
						],
						'payment_method' => [
							'text' => $this->language->get('export_order_payment_method'),
							'value' => $order['payment_method']
						],
						'shipping_firstname' => [
							'text' => $this->language->get('export_order_shipping_firstname'),
							'value' => $order['shipping_firstname']
						],
						'shipping_lastname' => [
							'text' => $this->language->get('export_order_shipping_lastname'),
							'value' => $order['shipping_lastname']
						],
						'shipping_company' => [
							'text' => $this->language->get('export_order_shipping_company'),
							'value' => $order['shipping_company']
						],
						'shipping_address_1' => [
							'text' => $this->language->get('export_order_shipping_address1'),
							'value' => $order['shipping_address_1']
						],
						'shipping_address_2' => [
							'text' => $this->language->get('export_order_shipping_address2'),
							'value' => $order['shipping_address_2']
						],
						'shipping_city' => [
							'text' => $this->language->get('export_order_shipping_city'),
							'value' => $order['shipping_city']
						],
						'shipping_postcode' => [
							'text' => $this->language->get('export_order_shipping_postcode'),
							'value' => $order['shipping_postcode']
						],
						'shipping_country' => [
							'text' => $this->language->get('export_order_shipping_country'),
							'value' => $order['shipping_country']
						],
						'shipping_zone' => [
							'text' => $this->language->get('export_order_shipping_zone'),
							'value' => $order['shipping_zone']
						],
						'shipping_custom_field' => [
							'text' => $this->language->get('export_order_shipping_customfield'),
							'value' => $order['shipping_custom_field']
						],
						'shipping_method' => [
							'text' => $this->language->get('export_order_shipping_method'),
							'value' => $order['shipping_method']
						],
						'comment' => [
							'text' => $this->language->get('export_order_comment'),
							'value' => $order['comment']
						],
						'order_othertotal' => [
							'text' => $this->language->get('export_order_othertotal'),
							'value' => $order_othertotal
						],
						'total' => [
							'text' => $this->language->get('export_order_total'),
							'value' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'])
						],
						'ip' => [
							'text' => $this->language->get('export_order_ip'),
							'value' => $order['ip']
						],
						'forwarded_ip' => [
							'text' => $this->language->get('export_order_forwaredip'),
							'value' => $order['forwarded_ip']
						],
						'user_agent' => [
							'text' => $this->language->get('export_order_useragent'),
							'value' => $order['user_agent']
						],
						'accept_language' => [
							'text' => $this->language->get('export_order_acceptlanguage'),
							'value' => $order['accept_language']
						],
						'date_added' => [
							'text' => $this->language->get('export_order_date_added'),
							'value' => $order['date_added']
						],
						'date_modified' => [
							'text' => $this->language->get('export_order_date_modified'),
							'value' => $order['date_modified']
						],
						'order_history' => [
							'text' => $this->language->get('export_order_history'),
							'value' => $order_history
						],
						'order_product' => [
							'text' => $this->language->get('export_order_product'),
							'value' => $order_product
						],
						'order_voucher' => [
							'text' => $this->language->get('export_order_voucher'),
							'value' => $order_voucher
						],
					];

					$xml_order = $xml->createElement("order");
					$xml_orders->appendChild($xml_order);

					foreach ($export_data as $key => $edata) {
						if ($edata['value'] == '') {
							$edata['value'] = ' ';
						}
						// if ($key == 'custom_field' || $key == 'shipping_custom_field' || $key == 'payment_custom_field') {
						// 	continue;
						// }
						// // Note: cover custom fields and json encoded data coming from db or create for xml files
						// if ($key == 'order_voucher' || $key == 'order_product' || $key == 'order_history' || $key == 'order_othertotal') {
						// 	continue;
						// }

						$xml_edata = $xml->createElement($key, $edata['value']);
						// $xml_edata->setAttribute("text", $edata['text']);
						$xml_order->appendChild($xml_edata);

						// $xml_attr = $xml->createAttribute('text');
						// $xml_attr->value = $edata['text'];
						// $xml_edata->appendChild($xml_attr);
					}
				}

				$file_name = 'customer_order.xml';
				$file_to_save = DIR_UPLOAD . $file_name;

				// echo $xml->saveXML();
				$xml->save($file_to_save);

			}

			// record download orders activity and Add to request log

			$request_data = [
				'customer_id' => $this->customer->getId(),
			];
			// 01-05-2022: updation start
			$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEDOWNLOADORDER, $request_data);
			// 01-05-2022: updation end
			$json['redirect'] = str_replace('&amp;', '&', $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr/fileDownload', 'file_name='. $file_name .'&file_format='. $file_format, true));
		}

		$this->response->setOutput(json_encode($json));
	}
	public function getGDPRRequests() {
		// download all gdpr requests data as csv
		$this->response->addHeader('Content-Type: application/json');
		$json = [];
		if (!$this->config->get('mpgdpr_status')) {
			$this->response->setOutput(json_encode([]));
			$this->response->output();
			exit;
		}
		$this->load->language($this->extension_path . 'mpgdpr/mpgdpr');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
		if (!$this->customer->getId()) {
			$json['warning'] =  $this->language->get('error_login_required');
		}

		if (!$json) {
			// process all data
			$file_format = 'csv';
			$file_name = 'customer_mpgdpr_requestlist.csv';
			if (in_array($this->config->get('mpgdpr_export_format'), ['csv','xls','xlsx','json','xml'])) {
				$file_format = $this->config->get('mpgdpr_export_format');
			}

			$requestlists = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerMpGdprRequestLists($this->customer->getId(), $this->customer->getEmail());

			if (in_array($file_format, ['csv','xls','xlsx'])) {

				$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

				$i = 1;
				$char = 'A';

				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_requestlist_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_requestlist_email'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_requestlist_type'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_requestlist_serverip'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_requestlist_clientip'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_requestlist_useragent'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_requestlist_acceptlanguage'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_requestlist_dateadded'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);

				// Background Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('1A017FBE');
				// Font Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF');
				// process all data

				foreach ($requestlists as $requestlist) {
					$char_value = 'A'; $i++;

					$email = $requestlist['email'];
					if (empty($email)) {
						$email = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerIdEmail($requestlist['customer_id']);
					}

					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $requestlist['mpgdpr_requestlist_id']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $email);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $this->mpgdpr->getRequestName($requestlist['requessttype']));
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $requestlist['server_ip']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $requestlist['client_ip']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $requestlist['user_agent']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $requestlist['accept_language']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $requestlist['date_added']);
				}

				// Find Format
				if ($file_format == 'xls') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($objPHPExcel);
					$file_name = 'customer_mpgdpr_requestlist.xls';
				} elseif ($file_format == 'xlsx') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_mpgdpr_requestlist.xlsx';
				} elseif ($file_format == 'csv') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($objPHPExcel);
					$file_name = 'customer_mpgdpr_requestlist.csv';
				} else {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_mpgdpr_requestlist.xlsx';
				}

				$file_to_save = DIR_UPLOAD . $file_name;
				$objWriter->save(DIR_UPLOAD . $file_name);
			}

			if ($file_format == 'json') {
				$export_data = [];

				// process all data

				foreach ($requestlists as $requestlist) {

					$email = $requestlist['email'];
					if (empty($email)) {
						$email = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerIdEmail($requestlist['customer_id']);
					}

					$export_data[] = [
						'requestlist_id' => [
							'text' => $this->language->get('export_requestlist_id'),
							'value' => $requestlist['mpgdpr_requestlist_id']
						],
						'email' => [
							'text' => $this->language->get('export_requestlist_email'),
							'value' => $email
						],
						'requessttype' => [
							'text' => $this->language->get('export_requestlist_type'),
							'value' => $this->mpgdpr->getRequestName($requestlist['requessttype'])
						],
						'server_ip' => [
							'text' => $this->language->get('export_requestlist_serverip'),
							'value' => $requestlist['server_ip']
						],
						'client_ip' => [
							'text' => $this->language->get('export_requestlist_clientip'),
							'value' => $requestlist['client_ip']
						],
						'user_agent' => [
							'text' => $this->language->get('export_requestlist_useragent'),
							'value' => $requestlist['user_agent']
						],
						'accept_language' => [
							'text' => $this->language->get('export_requestlist_acceptlanguage'),
							'value' => $requestlist['accept_language']
						],
						'date_added' => [
							'text' => $this->language->get('export_requestlist_dateadded'),
							'value' => $requestlist['date_added']
						],
					];
				}

				// create a file with name.json
				$file_name = 'customer_mpgdpr_requestlist.json';
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

				$xml_requestlists = $xml->createElement("requestlists");
				$xml->appendChild($xml_requestlists);

				// process all data

				foreach ($requestlists as $requestlist) {

					$email = $requestlist['email'];
					if (empty($email)) {
						$email = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerIdEmail($requestlist['customer_id']);
					}

					$export_data = [];

					$export_data = [
						'requestlist_id' => [
							'text' => $this->language->get('export_requestlist_id'),
							'value' => $requestlist['mpgdpr_requestlist_id']
						],
						'email' => [
							'text' => $this->language->get('export_requestlist_email'),
							'value' => $email
						],
						'requessttype' => [
							'text' => $this->language->get('export_requestlist_type'),
							'value' => $this->mpgdpr->getRequestName($requestlist['requessttype'])
						],
						'server_ip' => [
							'text' => $this->language->get('export_requestlist_serverip'),
							'value' => $requestlist['server_ip']
						],
						'client_ip' => [
							'text' => $this->language->get('export_requestlist_clientip'),
							'value' => $requestlist['client_ip']
						],
						'user_agent' => [
							'text' => $this->language->get('export_requestlist_useragent'),
							'value' => $requestlist['user_agent']
						],
						'accept_language' => [
							'text' => $this->language->get('export_requestlist_acceptlanguage'),
							'value' => $requestlist['accept_language']
						],
						'date_added' => [
							'text' => $this->language->get('export_requestlist_dateadded'),
							'value' => $requestlist['date_added']
						],
					];

					$xml_requestlist = $xml->createElement("requestlist");
					$xml_requestlists->appendChild($xml_requestlist);

					foreach ($export_data as $key => $edata) {
						if ($edata['value'] == '') {
							$edata['value'] = ' ';
						}
						// if ($key == 'custom_field') {
						// 	continue;
						// }

						$xml_edata = $xml->createElement($key, $edata['value']);
						// $xml_edata->setAttribute("text", $edata['text']);
						$xml_requestlist->appendChild($xml_edata);

						// $xml_attr = $xml->createAttribute('text');
						// $xml_attr->value = $edata['text'];
						// $xml_edata->appendChild($xml_attr);
					}
				}

				$file_name = 'customer_mpgdpr_requestlist.xml';
				$file_to_save = DIR_UPLOAD . $file_name;

				// echo $xml->saveXML();
				$xml->save($file_to_save);

			}

			// record download personal data activity and Add to request log

			$request_data = [
				'customer_id' => $this->customer->getId(),
			];
			// 01-05-2022: updation start
			$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEDOWNLOADGDPR, $request_data);
			// 01-05-2022: updation end
			$json['redirect'] = str_replace('&amp;', '&', $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr/fileDownload', 'file_name='. $file_name .'&file_format='. $file_format, true));
		}

		$this->response->setOutput(json_encode($json));
	}
	public function getWishlists() {
		// download wishlists data as csv
		$this->response->addHeader('Content-Type: application/json');
		$json = [];
		if (!$this->config->get('mpgdpr_status')) {
			$this->response->setOutput(json_encode([]));
			$this->response->output();
			exit;
		}
		$this->load->language($this->extension_path . 'mpgdpr/mpgdpr');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
		if (!$this->customer->getId()) {
			$json['warning'] =  $this->language->get('error_login_required');
		}

		if (!$json) {
			// process all data
			$file_format = 'csv';
			$file_name = 'customer_wishlist.csv';
			if (in_array($this->config->get('mpgdpr_export_format'), ['csv','xls','xlsx','json','xml'])) {
				$file_format = $this->config->get('mpgdpr_export_format');
			}

			$wishlists = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerWishLists($this->customer->getId());

			if (in_array($file_format, ['csv','xls','xlsx'])) {

				$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

				$i = 1;
				$char = 'A';

				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customer_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_product_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_product_name'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_product_model'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_product_price'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);

				if (VERSION >= '2.1.0.1') {
					$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_wishlist_date_added'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				}

				// Background Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('1A017FBE');
				// Font Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF');
				// process all data

				foreach ($wishlists as $wishlist) {
					$char_value = 'A'; $i++;

					$product_info = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getProduct($wishlist['product_id']);
					if ($product_info) {
						$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $wishlist['customer_id']);
						$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $wishlist['product_id']);
						$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $product_info['name']);
						$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $product_info['model']);
						$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']));

						if (VERSION >= '2.1.0.1') {
							$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $wishlist['date_added']);
						}
					}

				}

				// Find Format
				if ($file_format == 'xls') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($objPHPExcel);
					$file_name = 'customer_wishlist.xls';
				} elseif ($file_format == 'xlsx') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_wishlist.xlsx';
				} elseif ($file_format == 'csv') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($objPHPExcel);
					$file_name = 'customer_wishlist.csv';
				} else {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_wishlist.xlsx';
				}

				$file_to_save = DIR_UPLOAD . $file_name;
				$objWriter->save(DIR_UPLOAD . $file_name);
			}

			if ($file_format == 'json') {
				$export_data = [];

				// process all data
				$i = 0;
				foreach ($wishlists as $wishlist) {

					$product_info = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getProduct($wishlist['product_id']);
					if ($product_info) {

						$export_data[$i] = [
							'customer_id' => [
								'text' => $this->language->get('export_customer_id'),
								'value' => $wishlist['customer_id']
							],
							'product_id' => [
								'text' => $this->language->get('export_product_id'),
								'value' => $wishlist['product_id']
							],
							'name' => [
								'text' => $this->language->get('export_product_name'),
								'value' => $product_info['name']
							],
							'model' => [
								'text' => $this->language->get('export_product_model'),
								'value' => $product_info['model']
							],
							'price' => [
								'text' => $this->language->get('export_product_price'),
								'value' => $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
							],
						];

						if (VERSION >= '2.1.0.1') {
							$export_data[$i]['date_added'] = [
								'text' => $this->language->get('export_wishlist_date_added'),
								'value' => $wishlist['date_added']
							];
						}

						$i++;
					}

				}


				// create a file with name.json
				$file_name = 'customer_wishlist.json';
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

				$xml_wishlists = $xml->createElement("wishlists");
				$xml->appendChild($xml_wishlists);

				// process all data
				foreach ($wishlists as $wishlist) {

					$export_data = [];

					$product_info = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getProduct($wishlist['product_id']);
					if ($product_info) {

						$export_data = [
							'customer_id' => [
								'text' => $this->language->get('export_customer_id'),
								'value' => $wishlist['customer_id']
							],
							'product_id' => [
								'text' => $this->language->get('export_product_id'),
								'value' => $wishlist['product_id']
							],
							'name' => [
								'text' => $this->language->get('export_product_name'),
								'value' => $product_info['name']
							],
							'model' => [
								'text' => $this->language->get('export_product_model'),
								'value' => $product_info['model']
							],
							'price' => [
								'text' => $this->language->get('export_product_price'),
								'value' => $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
							],
						];

						if (VERSION >= '2.1.0.1') {
							$export_data['date_added'] = [
								'text' => $this->language->get('export_wishlist_date_added'),
								'value' => $wishlist['date_added']
							];
						}
					}


					$xml_wishlist = $xml->createElement("wishlist");
					$xml_wishlists->appendChild($xml_wishlist);

					foreach ($export_data as $key => $edata) {
						if ($edata['value'] == '') {
							$edata['value'] = ' ';
						}
						// if ($key == 'custom_field') {
						// 	continue;
						// }

						$xml_edata = $xml->createElement($key, $edata['value']);
						// $xml_edata->setAttribute("text", $edata['text']);
						$xml_wishlist->appendChild($xml_edata);

						// $xml_attr = $xml->createAttribute('text');
						// $xml_attr->value = $edata['text'];
						// $xml_edata->appendChild($xml_attr);
					}
				}

				$file_name = 'customer_wishlist.xml';
				$file_to_save = DIR_UPLOAD . $file_name;

				// echo $xml->saveXML();
				$xml->save($file_to_save);

			}
			// record download personal data activity and Add to request log

			$request_data = [
				'customer_id' => $this->customer->getId(),
			];
			// 01-05-2022: updation start
			$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEDOWNLOADWISHLIST, $request_data);
			// 01-05-2022: updation end
			$json['redirect'] = str_replace('&amp;', '&', $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr/fileDownload', 'file_name='. $file_name .'&file_format='. $file_format, true));
		}

		$this->response->setOutput(json_encode($json));
	}
	public function getTransactions() {
		// download wishlists data as csv
		$this->response->addHeader('Content-Type: application/json');
		$json = [];
		if (!$this->config->get('mpgdpr_status')) {
			$this->response->setOutput(json_encode([]));
			$this->response->output();
			exit;
		}
		$this->load->language($this->extension_path . 'mpgdpr/mpgdpr');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
		if (!$this->customer->getId()) {
			$json['warning'] =  $this->language->get('error_login_required');
		}

		if (!$json) {
			// process all data
			$file_format = 'csv';
			$file_name = 'customer_transaction.csv';
			if (in_array($this->config->get('mpgdpr_export_format'), ['csv','xls','xlsx','json','xml'])) {
				$file_format = $this->config->get('mpgdpr_export_format');
			}

			$transactions = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerTransactions($this->customer->getId());

			if (in_array($file_format, ['csv','xls','xlsx'])) {

				$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
				$i = 1;
				$char = 'A';

				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customer_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_order_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_transaction_description'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_transaction_amount'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_transaction_dateadded'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);

				// Background Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('1A017FBE');
				// Font Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF');

				// process all data
				// 01-05-2022: updation start
				if ($transactions) {

					foreach ($transactions as $transaction) {
						$char_value = 'A'; $i++;

						$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $transaction['customer_id']);
						$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $transaction['order_id']);
						$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $transaction['description']);
						$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $this->currency->format($transaction['amount'], $this->session->data['currency']));
						$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $transaction['date_added']);
					}

					$balance = $this->currency->format($this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerTransactionTotal($this->customer->getId()), $this->session->data['currency']);

					$char_value = 'A'; $i++;
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, '');
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, '');
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $this->language->get('export_transaction_balance'));
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $balance);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, '');
				}
				// 01-05-2022: updation end

				// Find Format
				if ($file_format == 'xls') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($objPHPExcel);
					$file_name = 'customer_transaction.xls';
				} elseif ($file_format == 'xlsx') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_transaction.xlsx';
				} elseif ($file_format == 'csv') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($objPHPExcel);
					$file_name = 'customer_transaction.csv';
				} else {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_transaction.xlsx';
				}

				$file_to_save = DIR_UPLOAD . $file_name;
				$objWriter->save(DIR_UPLOAD . $file_name);
			}

			if ($file_format == 'json') {
				$export_data = [];
				// process all data
				// 01-05-2022: updation start
				if ($transactions) {
					foreach ($transactions as $transaction) {
						$export_data[] = [
							'customer_id' => [
								'text' => $this->language->get('export_customer_id'),
								'value' => $transaction['customer_id']
							],
							'order_id' => [
								'text' => $this->language->get('export_order_id'),
								'value' => $transaction['order_id']
							],
							'description' => [
								'text' => $this->language->get('export_transaction_description'),
								'value' => $transaction['description']
							],
							'amount' => [
								'text' => $this->language->get('export_transaction_amount'),
								'value' => $this->currency->format($transaction['amount'], $this->session->data['currency'])
							],
							'date_added' => [
								'text' => $this->language->get('export_transaction_dateadded'),
								'value' => $transaction['date_added']
							],
							'balance' => [
								'text' => '',
								'value' => ''
							],
						];
					}
					$balance = $this->currency->format($this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerTransactionTotal($this->customer->getId()), $this->session->data['currency']);
					$export_data[] = [
						'customer_id' => [
							'text' => '',
							'value' => ''
						],
						'order_id' => [
							'text' => '',
							'value' => ''
						],
						'description' => [
							'text' => '',
							'value' => ''
						],
						'amount' => [
							'text' => '',
							'value' => ''
						],
						'date_added' => [
							'text' => '',
							'value' => ''
						],
						'balance' => [
							'text' => $this->language->get('export_transaction_balance'),
							'value' => $balance
						]
					];
				}
				// 01-05-2022: updation end

				// create a file with name.json
				$file_name = 'customer_transaction.json';
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

				$xml_transactions = $xml->createElement("transactions");
				$xml->appendChild($xml_transactions);

				// process all data
				// 01-05-2022: updation start
				if ($transactions) {
					foreach ($transactions as $transaction) {

						$export_data = [];

						$export_data = [
							'customer_id' => [
								'text' => $this->language->get('export_customer_id'),
								'value' => $transaction['customer_id']
							],
							'order_id' => [
								'text' => $this->language->get('export_order_id'),
								'value' => $transaction['order_id']
							],
							'description' => [
								'text' => $this->language->get('export_transaction_description'),
								'value' => $transaction['description']
							],
							'amount' => [
								'text' => $this->language->get('export_transaction_amount'),
								'value' => $this->currency->format($transaction['amount'], $this->session->data['currency'])
							],
							'date_added' => [
								'text' => $this->language->get('export_transaction_dateadded'),
								'value' => $transaction['date_added']
							],
							// 'balance' => [
							// 	'text' => '',
							// 	'value' => ''
							// ],
						];

						$xml_transaction = $xml->createElement("transaction");
						$xml_transactions->appendChild($xml_transaction);

						foreach ($export_data as $key => $edata) {
							if ($edata['value'] == '') {
								$edata['value'] = ' ';
							}
							// if ($key == 'custom_field') {
							// 	continue;
							// }

							$xml_edata = $xml->createElement($key, $edata['value']);
							// $xml_edata->setAttribute("text", $edata['text']);
							$xml_transaction->appendChild($xml_edata);

							// $xml_attr = $xml->createAttribute('text');
							// $xml_attr->value = $edata['text'];
							// $xml_edata->appendChild($xml_attr);
						}
					}

					$balance = $this->currency->format($this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerTransactionTotal($this->customer->getId()), $this->session->data['currency']);

					$xml_balance = $xml->createElement("balance", $balance);
					$xml->appendChild($xml_balance);
				}
				// 01-05-2022: updation end
				$file_name = 'customer_transaction.xml';
				$file_to_save = DIR_UPLOAD . $file_name;

				// echo $xml->saveXML();
				$xml->save($file_to_save);

			}
			// record download personal data activity and Add to request log

			$request_data = [
				'customer_id' => $this->customer->getId(),
			];
			// 01-05-2022: updation start
			$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEDOWNLOADHISTORYTRANSACTION, $request_data);
			// 01-05-2022: updation end
			$json['redirect'] = str_replace('&amp;', '&', $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr/fileDownload', 'file_name='. $file_name .'&file_format='. $file_format, true));
		}

		$this->response->setOutput(json_encode($json));
	}
	public function getHistory() {
		// download wishlists data as csv
		$this->response->addHeader('Content-Type: application/json');
		$json = [];
		if (!$this->config->get('mpgdpr_status')) {
			$this->response->setOutput(json_encode([]));
			$this->response->output();
			exit;
		}
		$this->load->language($this->extension_path . 'mpgdpr/mpgdpr');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
		if (!$this->customer->getId()) {
			$json['warning'] =  $this->language->get('error_login_required');
		}

		if (!$json) {
			// process all data
			$file_format = 'csv';
			$file_name = 'customer_history.csv';
			if (in_array($this->config->get('mpgdpr_export_format'), ['csv','xls','xlsx','json','xml'])) {
				$file_format = $this->config->get('mpgdpr_export_format');
			}

			$results = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerHistories($this->customer->getId());

			if (in_array($file_format, ['csv','xls','xlsx'])) {

				$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
				$i = 1;
				$char = 'A';

				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customer_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_history_description'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_history_dateadded'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);

				// Background Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('1A017FBE');
				// Font Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF');

				// process all data

				foreach ($results as $result) {
					$char_value = 'A'; $i++;

					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['customer_id']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['comment']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['date_added']);
				}

				// Find Format
				if ($file_format == 'xls') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($objPHPExcel);
					$file_name = 'customer_history.xls';
				} elseif ($file_format == 'xlsx') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_history.xlsx';
				} elseif ($file_format == 'csv') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($objPHPExcel);
					$file_name = 'customer_history.csv';
				} else {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_history.xlsx';
				}

				$file_to_save = DIR_UPLOAD . $file_name;
				$objWriter->save(DIR_UPLOAD . $file_name);
			}

			if ($file_format == 'json') {
				$export_data = [];

				// process all data
				foreach ($results as $result) {
					$export_data[] = [
						'customer_id' => [
							'text' => $this->language->get('export_customer_id'),
							'value' => $result['customer_id']
						],
						'comment' => [
							'text' => $this->language->get('export_history_description'),
							'value' => $result['comment']
						],
						'date_added' => [
							'text' => $this->language->get('export_history_dateadded'),
							'value' => $result['date_added']
						],
					];
				}

				// create a file with name.json
				$file_name = 'customer_history.json';
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

				$xml_histories = $xml->createElement("histories");
				$xml->appendChild($xml_histories);

				// process all data
				foreach ($results as $result) {

					$export_data = [];
					$export_data = [
						'customer_id' => [
							'text' => $this->language->get('export_customer_id'),
							'value' => $result['customer_id']
						],
						'comment' => [
							'text' => $this->language->get('export_history_description'),
							'value' => $result['comment']
						],
						'date_added' => [
							'text' => $this->language->get('export_history_dateadded'),
							'value' => $result['date_added']
						],
					];

					$xml_history = $xml->createElement("history");
					$xml_histories->appendChild($xml_history);

					foreach ($export_data as $key => $edata) {
						if ($edata['value'] == '') {
							$edata['value'] = ' ';
						}
						// if ($key == 'custom_field') {
						// 	continue;
						// }

						$xml_edata = $xml->createElement($key, $edata['value']);
						// $xml_edata->setAttribute("text", $edata['text']);
						$xml_history->appendChild($xml_edata);

						// $xml_attr = $xml->createAttribute('text');
						// $xml_attr->value = $edata['text'];
						// $xml_edata->appendChild($xml_attr);
					}
				}

				$file_name = 'customer_history.xml';
				$file_to_save = DIR_UPLOAD . $file_name;

				// echo $xml->saveXML();
				$xml->save($file_to_save);

			}
			// record download personal data activity and Add to request log

			$request_data = [
				'customer_id' => $this->customer->getId(),
			];
			// 01-05-2022: updation start
			$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEDOWNLOADHISTORYCUSTOMER, $request_data);
			// 01-05-2022: updation end
			$json['redirect'] = str_replace('&amp;', '&', $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr/fileDownload', 'file_name='. $file_name .'&file_format='. $file_format, true));
		}

		$this->response->setOutput(json_encode($json));
	}
	public function getSearchHistory() {
		// download wishlists data as csv
		$this->response->addHeader('Content-Type: application/json');
		$json = [];
		if (!$this->config->get('mpgdpr_status')) {
			$this->response->setOutput(json_encode([]));
			$this->response->output();
			exit;
		}
		$this->load->language($this->extension_path . 'mpgdpr/mpgdpr');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
		if (!$this->customer->getId()) {
			$json['warning'] =  $this->language->get('error_login_required');
		}

		if (!$json) {
			// process all data
			$file_format = 'csv';
			$file_name = 'customer_search_history.csv';
			if (in_array($this->config->get('mpgdpr_export_format'), ['csv','xls','xlsx','json','xml'])) {
				$file_format = $this->config->get('mpgdpr_export_format');
			}

			$searchhistories = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerSearchHistory($this->customer->getId());

			if (in_array($file_format, ['csv','xls','xlsx'])) {

				$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
				$i = 1;
				$char = 'A';

				// $objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customersearch_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customersearch_keyword'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customersearch_category'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customersearch_insubcategory'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customersearch_indescription'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customersearch_numproducts'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customersearch_ip'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customersearch_dateadded'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);

				// Background Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('1A017FBE');
				// Font Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF');

				// process all data

				foreach ($searchhistories as $searchhistory) {
					$char_value = 'A'; $i++;

					$category_name = '';

					$category_info = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCategory($searchhistory['category_id'], $searchhistory['language_id'], $searchhistory['store_id']);

					if ($category_info) {
						$category_name = $category_info['name'];
					}


					// $objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $searchhistory['customer_search_id']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $searchhistory['keyword']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $category_name);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, ($searchhistory['sub_category'] ? $this->language->get('text_yes') : $this->language->get('text_no')));
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, ($searchhistory['description'] ? $this->language->get('text_yes') : $this->language->get('text_no')));
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $searchhistory['products']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $searchhistory['ip']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $searchhistory['date_added']);
				}

				// Find Format
				if ($file_format == 'xls') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($objPHPExcel);
					$file_name = 'customer_search_history.xls';
				} elseif ($file_format == 'xlsx') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_search_history.xlsx';
				} elseif ($file_format == 'csv') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($objPHPExcel);
					$file_name = 'customer_search_history.csv';
				} else {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_search_history.xlsx';
				}

				$file_to_save = DIR_UPLOAD . $file_name;
				$objWriter->save(DIR_UPLOAD . $file_name);
			}

			if ($file_format == 'json') {
				$export_data = [];
				// process all data

				foreach ($searchhistories as $searchhistory) {
					$category_name = '';

					$category_info = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCategory($searchhistory['category_id'], $searchhistory['language_id'], $searchhistory['store_id']);

					if ($category_info) {
						$category_name = $category_info['name'];
					}
					$export_data[] = [
						// 'customer_search_id' => [
						// 	'text' => $this->language->get('export_customersearch_id'),
						// 	'value' => $searchhistory['customer_search_id']
						// ],
						'keyword' => [
							'text' => $this->language->get('export_customersearch_keyword'),
							'value' => $searchhistory['keyword']
						],
						'category' => [
							'text' => $this->language->get('export_customersearch_category'),
							'value' => $category_name
						],
						'insubcategory' => [
							'text' => $this->language->get('export_customersearch_insubcategory'),
							'value' => ($searchhistory['sub_category'] ? $this->language->get('text_yes') : $this->language->get('text_no'))
						],
						'indescription' => [
							'text' => $this->language->get('export_customersearch_indescription'),
							'value' => ($searchhistory['description'] ? $this->language->get('text_yes') : $this->language->get('text_no'))
						],
						'numproducts' => [
							'text' => $this->language->get('export_customersearch_numproducts'),
							'value' => $searchhistory['products']
						],
						'ip' => [
							'text' => $this->language->get('export_customersearch_ip'),
							'value' => $searchhistory['ip']
						],
						'date_added' => [
							'text' => $this->language->get('export_customersearch_dateadded'),
							'value' => $searchhistory['date_added']
						],
					];
				}

				// create a file with name.json
				$file_name = 'customer_search_history.json';
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

				$xml_searchhistories = $xml->createElement("searchhistories");
				$xml->appendChild($xml_searchhistories);

				// process all data

				foreach ($searchhistories as $searchhistory) {
					$export_data = [];

					$category_name = '';

					$category_info = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCategory($searchhistory['category_id'], $searchhistory['language_id'], $searchhistory['store_id']);

					if ($category_info) {
						$category_name = $category_info['name'];
					}
					$export_data = [
						// 'customer_search_id' => [
						// 	'text' => $this->language->get('export_customersearch_id'),
						// 	'value' => $searchhistory['customer_search_id']
						// ],
						'keyword' => [
							'text' => $this->language->get('export_customersearch_keyword'),
							'value' => $searchhistory['keyword']
						],
						'category' => [
							'text' => $this->language->get('export_customersearch_category'),
							'value' => $category_name
						],
						'insubcategory' => [
							'text' => $this->language->get('export_customersearch_insubcategory'),
							'value' => ($searchhistory['sub_category'] ? $this->language->get('text_yes') : $this->language->get('text_no'))
						],
						'indescription' => [
							'text' => $this->language->get('export_customersearch_indescription'),
							'value' => ($searchhistory['description'] ? $this->language->get('text_yes') : $this->language->get('text_no'))
						],
						'numproducts' => [
							'text' => $this->language->get('export_customersearch_numproducts'),
							'value' => $searchhistory['products']
						],
						'ip' => [
							'text' => $this->language->get('export_customersearch_ip'),
							'value' => $searchhistory['ip']
						],
						'date_added' => [
							'text' => $this->language->get('export_customersearch_dateadded'),
							'value' => $searchhistory['date_added']
						],
					];


					$xml_searchhistory = $xml->createElement("searchhistory");
					$xml_searchhistories->appendChild($xml_searchhistory);

					foreach ($export_data as $key => $edata) {
						if ($edata['value'] == '') {
							$edata['value'] = ' ';
						}
						// if ($key == 'custom_field') {
						// 	continue;
						// }

						$xml_edata = $xml->createElement($key, $edata['value']);
						// $xml_edata->setAttribute("text", $edata['text']);
						$xml_searchhistory->appendChild($xml_edata);

						// $xml_attr = $xml->createAttribute('text');
						// $xml_attr->value = $edata['text'];
						// $xml_edata->appendChild($xml_attr);
					}
				}

				$file_name = 'customer_search_history.xml';
				$file_to_save = DIR_UPLOAD . $file_name;

				// echo $xml->saveXML();
				$xml->save($file_to_save);

			}
			// record download personal data activity and Add to request log

			$request_data = [
				'customer_id' => $this->customer->getId(),
			];
			// 01-05-2022: updation start
			$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEDOWNLOADHISTORYSEARCH, $request_data);
			// 01-05-2022: updation end
			$json['redirect'] = str_replace('&amp;', '&', $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr/fileDownload', 'file_name='. $file_name .'&file_format='. $file_format, true));
		}

		$this->response->setOutput(json_encode($json));
	}
	public function getRewardPointsHistory() {
		// download wishlists data as csv
		$this->response->addHeader('Content-Type: application/json');
		$json = [];
		if (!$this->config->get('mpgdpr_status')) {
			$this->response->setOutput(json_encode([]));
			$this->response->output();
			exit;
		}
		$this->load->language($this->extension_path . 'mpgdpr/mpgdpr');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
		if (!$this->customer->getId()) {
			$json['warning'] =  $this->language->get('error_login_required');
		}

		if (!$json) {
			// process all data.
			$file_format = 'csv';
			$file_name = 'customer_reward_points.csv';
			if (in_array($this->config->get('mpgdpr_export_format'), ['csv','xls','xlsx','json','xml'])) {
				$file_format = $this->config->get('mpgdpr_export_format');
			}

			$results = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerRewardPoints($this->customer->getId());

			if (in_array($file_format, ['csv','xls','xlsx'])) {

				$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
				$i = 1;
				$char = 'A';

				// $objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customerreward_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customerreward_orderid'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customerreward_description'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customerreward_points'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customerreward_dateadded'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);

				// Background Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('1A017FBE');
				// Font Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF');

				// process all data
				// 01-05-2022: updation start
				if ($results) {
					foreach ($results as $result) {
						$char_value = 'A'; $i++;

						// $objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['customer_reward_id']);
						$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['order_id']);
						$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['description']);
						$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['points']);
						$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['date_added']);
					}

					$balance = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerRewardTotal($this->customer->getId());

					$char_value = 'A'; $i++;

					// $objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, '');
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, '');
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $this->language->get('export_customerreward_balance'));
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $balance);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, '');
				}
				// 01-05-2022: updation end
				// Find Format
				if ($file_format == 'xls') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($objPHPExcel);
					$file_name = 'customer_reward_points.xls';
				} elseif ($file_format == 'xlsx') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_reward_points.xlsx';
				} elseif ($file_format == 'csv') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($objPHPExcel);
					$file_name = 'customer_reward_points.csv';
				} else {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_reward_points.xlsx';
				}

				$file_to_save = DIR_UPLOAD . $file_name;
				$objWriter->save(DIR_UPLOAD . $file_name);
			}

			if ($file_format == 'json') {
				$export_data = [];

				// process all data
				// 01-05-2022: updation start
				if ($results) {
					foreach ($results as $result) {
						$export_data[] = [
							// 'customer_reward_id' => [
							// 	'text' => $this->language->get('export_customerreward_id'),
							// 	'value' => $result['customer_reward_id']
							// ],
							'order_id' => [
								'text' => $this->language->get('export_customerreward_orderid'),
								'value' => $result['order_id']
							],
							'description' => [
								'text' => $this->language->get('export_customerreward_description'),
								'value' => $result['description']
							],
							'points' => [
								'text' => $this->language->get('export_customerreward_points'),
								'value' => $result['points']
							],
							'date_added' => [
								'text' => $this->language->get('export_customerreward_dateadded'),
								'value' => $result['date_added']
							],
							'balance' => [
								'text' => $this->language->get('export_customerreward_balance'),
								'value' => ''
							],
						];
					}

					$balance = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerRewardTotal($this->customer->getId());

					$export_data[] = [
						// 'customer_reward_id' => [
						// 	'text' => $this->language->get('export_customerreward_id'),
						// 	'value' => ''
						// ],
						'order_id' => [
							'text' => $this->language->get('export_customerreward_orderid'),
							'value' => ''
						],
						'description' => [
							'text' => $this->language->get('export_customerreward_description'),
							'value' => ''
						],
						'points' => [
							'text' => $this->language->get('export_customerreward_points'),
							'value' => ''
						],
						'date_added' => [
							'text' => $this->language->get('export_customerreward_dateadded'),
							'value' => ''
						],
						'balance' => [
							'text' => $this->language->get('export_customerreward_balance'),
							'value' => $balance
						],
					];
				}
				// 01-05-2022: updation end
				// create a file with name.json
				$file_name = 'customer_reward_points.json';
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

				$xml_rewardpoints = $xml->createElement("rewardpoints");
				$xml->appendChild($xml_rewardpoints);

				// process all data
				// 01-05-2022: updation start
				if ($results) {
					foreach ($results as $result) {
						$export_data = [];

						$export_data = [
							// 'customer_reward_id' => [
							// 	'text' => $this->language->get('export_customerreward_id'),
							// 	'value' => $result['customer_reward_id']
							// ],
							'order_id' => [
								'text' => $this->language->get('export_customerreward_orderid'),
								'value' => $result['order_id']
							],
							'description' => [
								'text' => $this->language->get('export_customerreward_description'),
								'value' => $result['description']
							],
							'points' => [
								'text' => $this->language->get('export_customerreward_points'),
								'value' => $result['points']
							],
							'date_added' => [
								'text' => $this->language->get('export_customerreward_dateadded'),
								'value' => $result['date_added']
							],
							// 'balance' => [
							// 	'text' => $this->language->get('export_customerreward_balance'),
							// 	'value' => ''
							// ],
						];

						$xml_rewardpoint = $xml->createElement("rewardpoint");
						$xml_rewardpoints->appendChild($xml_rewardpoint);

						foreach ($export_data as $key => $edata) {
							if ($edata['value'] == '') {
								$edata['value'] = ' ';
							}
							// if ($key == 'custom_field') {
							// 	continue;
							// }

							$xml_edata = $xml->createElement($key, $edata['value']);
							// $xml_edata->setAttribute("text", $edata['text']);
							$xml_rewardpoint->appendChild($xml_edata);

							// $xml_attr = $xml->createAttribute('text');
							// $xml_attr->value = $edata['text'];
							// $xml_edata->appendChild($xml_attr);
						}
					}

					$balance = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerRewardTotal($this->customer->getId());
					$xml_balance = $xml->createElement("balance", $balance);
					$xml->appendChild($xml_balance);
				}
				// 01-05-2022: updation start
				$file_name = 'customer_reward_points.xml';
				$file_to_save = DIR_UPLOAD . $file_name;

				// echo $xml->saveXML();
				$xml->save($file_to_save);

			}
			// record download personal data activity and Add to request log

			$request_data = [
				'customer_id' => $this->customer->getId(),
			];
			// 01-05-2022: updation start
			$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEDOWNLOADHISTORYREWARD, $request_data);
			// 01-05-2022: updation end
			$json['redirect'] = str_replace('&amp;', '&', $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr/fileDownload', 'file_name='. $file_name .'&file_format='. $file_format, true));
		}

		$this->response->setOutput(json_encode($json));
	}
	public function getActivityHistory() {
		// download wishlists data as csv
		$this->response->addHeader('Content-Type: application/json');
		$json = [];
		if (!$this->config->get('mpgdpr_status')) {
			$this->response->setOutput(json_encode([]));
			$this->response->output();
			exit;
		}
		$this->load->language($this->extension_path . 'mpgdpr/mpgdpr');
		$this->load->language($this->extension_path . 'mpgdpr/gdpr');
		$this->load->model($this->extension_path . 'mpgdpr/mpgdpr');
		if (!$this->customer->getId()) {
			$json['warning'] =  $this->language->get('error_login_required');
		}

		if (!$json) {
			// process all data
			$file_format = 'csv';
			$file_name = 'customer_activities.csv';
			if (in_array($this->config->get('mpgdpr_export_format'), ['csv','xls','xlsx','json','xml'])) {
				$file_format = $this->config->get('mpgdpr_export_format');
			}

			$results = $this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->getCustomerActivities($this->customer->getId());

			if (in_array($file_format, ['csv','xls','xlsx'])) {

				$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
				$i = 1;
				$char = 'A';

				// $objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customeractivity_id'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customeractivity_key'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customeractivity_data'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customeractivity_ip'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);
				$objPHPExcel->getActiveSheet()->setCellValue($char .$i, $this->language->get('export_customeractivity_dateadded'))->getColumnDimension($char)->setAutoSize(true); $objPHPExcel->getActiveSheet()->getStyle($char++ .$i)->getAlignment()->setWrapText(true);

				// Background Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('1A017FBE');
				// Font Color
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FFFFFFFF');

				// process all data

				foreach ($results as $result) {
					$char_value = 'A'; $i++;

					// $objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['customer_activity_id']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['key']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['data']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['ip']);
					$objPHPExcel->getActiveSheet()->setCellValue($char_value++ .$i, $result['date_added']);
				}

				// Find Format
				if ($file_format == 'xls') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($objPHPExcel);
					$file_name = 'customer_activities.xls';
				} elseif ($file_format == 'xlsx') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_activities.xlsx';
				} elseif ($file_format == 'csv') {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($objPHPExcel);
					$file_name = 'customer_activities.csv';
				} else {
					$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
					$file_name = 'customer_activities.xlsx';
				}

				$file_to_save = DIR_UPLOAD . $file_name;
				$objWriter->save(DIR_UPLOAD . $file_name);
			}

			if ($file_format == 'json') {
				$export_data = [];
				// process all data

				foreach ($results as $result) {
					$export_data[] = [
						// 'customer_activity_id' => [
						// 	'text' => $this->language->get('export_customeractivity_id'),
						// 	'value' => $result['customer_activity_id']
						// ],
						'key' => [
							'text' => $this->language->get('export_customeractivity_key'),
							'value' => $result['key']
						],
						'data' => [
							'text' => $this->language->get('export_customeractivity_data'),
							'value' => $result['data']
						],
						'ip' => [
							'text' => $this->language->get('export_customeractivity_ip'),
							'value' => $result['ip']
						],
						'date_added' => [
							'text' => $this->language->get('export_customeractivity_dateadded'),
							'value' => $result['date_added']
						],

					];
				}

				// create a file with name.json
				$file_name = 'customer_activities.json';
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

				$xml_customeractivities = $xml->createElement("customeractivities");
				$xml->appendChild($xml_customeractivities);

				// process all data

				foreach ($results as $result) {
					$export_data = [];

					$export_data = [
						// 'customer_activity_id' => [
						// 	'text' => $this->language->get('export_customeractivity_id'),
						// 	'value' => $result['customer_activity_id']
						// ],
						'key' => [
							'text' => $this->language->get('export_customeractivity_key'),
							'value' => $result['key']
						],
						'data' => [
							'text' => $this->language->get('export_customeractivity_data'),
							'value' => $result['data']
						],
						'ip' => [
							'text' => $this->language->get('export_customeractivity_ip'),
							'value' => $result['ip']
						],
						'date_added' => [
							'text' => $this->language->get('export_customeractivity_dateadded'),
							'value' => $result['date_added']
						],

					];

					$xml_customeractivity = $xml->createElement("customeractivity");
					$xml_customeractivities->appendChild($xml_customeractivity);

					foreach ($export_data as $key => $edata) {
						if ($edata['value'] == '') {
							$edata['value'] = ' ';
						}
						// if ($key == 'custom_field') {
						// 	continue;
						// }

						$xml_edata = $xml->createElement($key, $edata['value']);
						// $xml_edata->setAttribute("text", $edata['text']);
						$xml_customeractivity->appendChild($xml_edata);

						// $xml_attr = $xml->createAttribute('text');
						// $xml_attr->value = $edata['text'];
						// $xml_edata->appendChild($xml_attr);
					}
				}

				$file_name = 'customer_activities.xml';
				$file_to_save = DIR_UPLOAD . $file_name;

				// echo $xml->saveXML();
				$xml->save($file_to_save);

			}
			// record download personal data activity and Add to request log

			$request_data = [
				'customer_id' => $this->customer->getId(),
			];
			// 01-05-2022: updation start
			$this->{'model_'. $this->extension_model .'mpgdpr_mpgdpr'}->addRequest(\Mpgdpr\Mpgdpr :: CODEDOWNLOADHISTORYACTIVITY, $request_data);
			// 01-05-2022: updation end
			$json['redirect'] = str_replace('&amp;', '&', $this->url->link($this->extension_path . 'mpgdpr/account/mpgdpr/fileDownload', 'file_name='. $file_name .'&file_format='. $file_format, true));
		}

		$this->response->setOutput(json_encode($json));
	}
}