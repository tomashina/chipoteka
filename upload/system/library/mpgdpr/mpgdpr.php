<?php
namespace Mpgdpr;
/**
 * https://cookieconsent.insites.com/documentation/about-cookie-consent/
 * https://github.com/insites/cookieconsent/
 */

class Mpgdpr extends \Mpgdpr\Modulepoints {

	use \Mpgdpr\trait_mpgdpr;

	const CODEREQUESTDELETEME = 'REQUEST_DELETEME';
	const CODEREQUESTPERSONALDATA = 'REQUEST_PERSONAL_DATA';
	/*13 sep 2019 gdpr session starts*/
	const CODEREQUESTRESSTRICTDATAPROCESSING = 'REQUEST_RESTRICT_DATA_PROCESSING';
	/*13 sep 2019 gdpr session ends*/
	const CODEDOWNLOADPERSONALINFO = 'DOWNLOAD_PERSONAL_INFO';
	const CODEDOWNLOADORDER = 'DOWNLOAD_ORDER';
	/*13 sep 2019 gdpr session starts*/
	/* No use of order history, order product, order vouchers. We include them in download order*/
	// const CODEDOWNLOADORDERHISTORY = 'DOWNLOAD_ORDER_HISTORY';
	// const CODEDOWNLOADORDERPRODUCT = 'DOWNLOAD_ORDER_PRODUCT';
	// const CODEDOWNLOADORDERVOUCHER = 'DOWNLOAD_ORDER_VOUCHER';
	/*13 sep 2019 gdpr session ends*/
	const CODEDOWNLOADADDRESS = 'DOWNLOAD_ADDRESS';
	const CODEDOWNLOADGDPR = 'DOWNLOAD_GDPR';
	const CODEDOWNLOADWISHLIST = 'DOWNLOAD_WISHLIST';
	const CODEDOWNLOADHISTORYTRANSACTION = 'DOWNLOAD_HISTORY_TRANSACTION';
	const CODEDOWNLOADHISTORYCUSTOMER = 'DOWNLOAD_HISTORY_CUSTOMER';
	const CODEDOWNLOADHISTORYSEARCH = 'DOWNLOAD_HISTORY_SEARCH';
	const CODEDOWNLOADHISTORYREWARD = 'DOWNLOAD_HISTORY_REWARD';
	const CODEDOWNLOADHISTORYACTIVITY = 'DOWNLOAD_HISTORY_ACTIVITY';

	// policy acceptance
	const CODEPOLICYACCEPTCONTACTUS = 'POLICY_ACCEPT_CONTACTUS';
	const CODEPOLICYACCEPTREGISTER = 'POLICY_ACCEPT_REGISTER';
	const CODEPOLICYACCEPTCHECKOUT = 'POLICY_ACCEPT_CHECKOUT';
	const CODEPOLICYACCEPTCOOKIECONSENT = 'POLICY_ACCEPT_COOKIECONSENT';

	// Access Request Status IDS
	const REQUESTACCESS_EXPIRE = '0'; // on verification fail
	const REQUESTACCESS_CONFIRMED = '1'; // on verification success
	const REQUESTACCESS_AWATING = '2'; // when create new request
	const REQUESTACCESS_REPORTSEND = '3'; // update by admin
	const REQUESTACCESS_DENY = '4'; // update by admin

	// Anonymouse/Deletion Request Status IDS
	const REQUESTANONYMOUSE_EXPIRE = '0'; // on verification fail
	const REQUESTANONYMOUSE_CONFIRMED = '1'; // on verification success
	const REQUESTANONYMOUSE_AWATING = '2'; // when create new request
	const REQUESTANONYMOUSE_COMPLETE = '3'; // update by admin
	const REQUESTANONYMOUSE_DENY = '4'; // update by admin

	private $logger;
	public function log($message, $write=1) {
		if ($write) {
			$this->logger->write($message);
		}
	}
	// 01-05-2022: updation start
	public function textEditor(&$data) {
		if (!isset($data['summernote'])) {
			$data['summernote']= '';
		}
		return $this->view('mpgdpr/texteditor', $data);
	}
	// 01-05-2022: updation end
	public function __construct($registry) 	{
		parent:: __construct($registry);
		$this->igniteTraitMpGdpr($registry);

		// do any startup work here
		$this->load->language($this->extension_path . 'mpgdpr/requests');

		$this->logger = new \Log('mpgdpr.log');
	}
	/*13 sep 2019 gdpr session starts*/
	public function getRequestTypes($getRequests=[]) {
	/*13 sep 2019 gdpr session ends*/
		$data = [];
		$data[] = [
			'code' => self :: CODEREQUESTDELETEME,
			'value' => $this->language->get('text_'. self :: CODEREQUESTDELETEME)
		];
		$data[] = [
			'code' => self :: CODEREQUESTPERSONALDATA,
			'value' => $this->language->get('text_'. self :: CODEREQUESTPERSONALDATA)
		];
		/*13 sep 2019 gdpr session starts*/
		$data[] = [
			'code' => self :: CODEREQUESTRESSTRICTDATAPROCESSING,
			'value' => $this->language->get('text_'. self :: CODEREQUESTRESSTRICTDATAPROCESSING)
		];
		/*13 sep 2019 gdpr session ends*/
		$data[] = [
			'code' => self :: CODEDOWNLOADPERSONALINFO,
			'value' => $this->language->get('text_'. self :: CODEDOWNLOADPERSONALINFO)
		];
		$data[] = [
			'code' => self :: CODEDOWNLOADORDER,
			'value' => $this->language->get('text_'. self :: CODEDOWNLOADORDER)
		];
		/*13 sep 2019 gdpr session starts*/
		/* No use of order history, order product, order vouchers. We include them in download order*/
		// $data[] = [
		// 	'code' => self :: CODEDOWNLOADORDERHISTORY,
		// 	'value' => $this->language->get('text_'. self :: CODEDOWNLOADORDERHISTORY)
		// ];
		// $data[] = [
		// 	'code' => self :: CODEDOWNLOADORDERPRODUCT,
		// 	'value' => $this->language->get('text_'. self :: CODEDOWNLOADORDERPRODUCT)
		// ];
		// $data[] = [
		// 	'code' => self :: CODEDOWNLOADORDERVOUCHER,
		// 	'value' => $this->language->get('text_'. self :: CODEDOWNLOADORDERVOUCHER)
		// ];
		/*13 sep 2019 gdpr session ends*/
		$data[] = [
			'code' => self :: CODEDOWNLOADADDRESS,
			'value' => $this->language->get('text_'. self :: CODEDOWNLOADADDRESS)
		];
		$data[] = [
			'code' => self :: CODEDOWNLOADGDPR,
			'value' => $this->language->get('text_'. self :: CODEDOWNLOADGDPR)
		];
		$data[] = [
			'code' => self :: CODEDOWNLOADWISHLIST,
			'value' => $this->language->get('text_'. self :: CODEDOWNLOADWISHLIST)
		];
		$data[] = [
			'code' => self :: CODEDOWNLOADHISTORYTRANSACTION,
			'value' => $this->language->get('text_'. self :: CODEDOWNLOADHISTORYTRANSACTION)
		];
		$data[] = [
			'code' => self :: CODEDOWNLOADHISTORYCUSTOMER,
			'value' => $this->language->get('text_'. self :: CODEDOWNLOADHISTORYCUSTOMER)
		];
		$data[] = [
			'code' => self :: CODEDOWNLOADHISTORYSEARCH,
			'value' => $this->language->get('text_'. self :: CODEDOWNLOADHISTORYSEARCH)
		];
		$data[] = [
			'code' => self :: CODEDOWNLOADHISTORYREWARD,
			'value' => $this->language->get('text_'. self :: CODEDOWNLOADHISTORYREWARD)
		];
		$data[] = [
			'code' => self :: CODEDOWNLOADHISTORYACTIVITY,
			'value' => $this->language->get('text_'. self :: CODEDOWNLOADHISTORYACTIVITY)
		];
		$data[] = [
			'code' => self :: CODEPOLICYACCEPTCONTACTUS,
			'value' => $this->language->get('text_'. self :: CODEPOLICYACCEPTCONTACTUS)
		];
		$data[] = [
			'code' => self :: CODEPOLICYACCEPTREGISTER,
			'value' => $this->language->get('text_'. self :: CODEPOLICYACCEPTREGISTER)
		];
		$data[] = [
			'code' => self :: CODEPOLICYACCEPTCHECKOUT,
			'value' => $this->language->get('text_'. self :: CODEPOLICYACCEPTCHECKOUT)
		];
		$data[] = [
			'code' => self :: CODEPOLICYACCEPTCOOKIECONSENT,
			'value' => $this->language->get('text_'. self :: CODEPOLICYACCEPTCOOKIECONSENT)
		];
		/*13 sep 2019 gdpr session starts*/
		if (!empty($getRequests) && is_array($getRequests)) {
			foreach ($data as $key => $value) {
				if (!in_array($value['code'], $getRequests)) {
					unset($data[$key]);
				}
			}
		}
		/*13 sep 2019 gdpr session ends*/
		return $data;
	}

	public function anonymouseCustomerGDPRData($customer_id) {

	}
	public function install() {
		// create all tables here
		/*--
		-- Table structure for table `oc_mpgdpr_datarequest`
		--*/
		// 01-05-2022: updation start
		// `email` varchar(96) NOT NULL,
		// 01-05-2022: updation end
		$this->db->query("CREATE TABLE IF NOT EXISTS `". DB_PREFIX ."mpgdpr_datarequest` (
			`mpgdpr_datarequest_id` int(11) NOT NULL AUTO_INCREMENT,
			`customer_id` int(11) NOT NULL,
			`email` varchar(96) NOT NULL,
			`store_id` int(11) NOT NULL,
			`server_ip` varchar(100) NOT NULL,
			`client_ip` varchar(100) NOT NULL,
			`user_agent` varchar(500) NOT NULL,
			`accept_language` varchar(255) NOT NULL,
			`status` tinyint(4) NOT NULL,
			`code` varchar(255) NOT NULL,
			`attachment` varchar(500) NOT NULL,
			`denyreason` text NOT NULL,
			`date_send` date NOT NULL,
			`session_id` varchar(255) NOT NULL,
			`date_added` datetime NOT NULL,
			`expire_on` datetime NOT NULL,
			`date_modified` datetime NOT NULL,
			PRIMARY KEY (`mpgdpr_datarequest_id`)
		) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
		");

		/*--
		-- Table structure for table `oc_mpgdpr_deleteme`
		--*/
		// 01-05-2022: updation start
		// `email` varchar(96) NOT NULL,
		// 01-05-2022: updation end
		$this->db->query("CREATE TABLE IF NOT EXISTS `". DB_PREFIX ."mpgdpr_deleteme` (
			`mpgdpr_deleteme_id` int(11) NOT NULL AUTO_INCREMENT,
			`customer_id` int(11) NOT NULL,
			`email` varchar(96) NOT NULL,
			`store_id` int(11) NOT NULL,
			`server_ip` varchar(100) NOT NULL,
			`client_ip` varchar(100) NOT NULL,
			`user_agent` varchar(500) NOT NULL,
			`accept_language` varchar(255) NOT NULL,
			`status` tinyint(4) NOT NULL,
			`code` varchar(255) NOT NULL,
			`date_deletion` date NOT NULL,
			`denyreason` text NOT NULL,
			`session_id` varchar(255) NOT NULL,
			`date_added` datetime NOT NULL,
			`expire_on` datetime NOT NULL,
			`date_modified` datetime NOT NULL,
			PRIMARY KEY (`mpgdpr_deleteme_id`)
		) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
		");

		/*--
		-- Table structure for table `oc_mpgdpr_policyacceptance`
		--*/

		$this->db->query("CREATE TABLE IF NOT EXISTS `". DB_PREFIX ."mpgdpr_policyacceptance` (
			`mpgdpr_policyacceptance_id` int(11) NOT NULL AUTO_INCREMENT,
			`requessttype` varchar(100) NOT NULL,
			`customer_id` int(11) NOT NULL,
			`email` varchar(96) NOT NULL,
			`store_id` int(11) NOT NULL,
			`policy_id` int(11) NOT NULL,
			`policy_title` varchar(255) NOT NULL,
			`policy_description` mediumtext NOT NULL,
			`server_ip` varchar(100) NOT NULL,
			`client_ip` varchar(100) NOT NULL,
			`user_agent` varchar(500) NOT NULL,
			`accept_language` varchar(255) NOT NULL,
			`status` tinyint(4) NOT NULL,
			`date_added` datetime NOT NULL,
			`date_modified` datetime NOT NULL,
			PRIMARY KEY (`mpgdpr_policyacceptance_id`)
		) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
		");

		/*--
		-- Table structure for table `oc_mpgdpr_requestlist`
		--*/
		/*13 sep 2019 gdpr session starts*/
		/*add new columns in mpgdpr_requestlist table. to pass any custom string as identified for request.*/
		// `custom_string` text NOT NULL,
		/*13 sep 2019 gdpr session end*/
		$this->db->query("CREATE TABLE IF NOT EXISTS `". DB_PREFIX ."mpgdpr_requestlist` (
			`mpgdpr_requestlist_id` int(11) NOT NULL AUTO_INCREMENT,
			`customer_id` int(11) NOT NULL,
			`email` varchar(96) NOT NULL,
			`store_id` int(11) NOT NULL,
			`requessttype` varchar(100) NOT NULL,
			`custom_string` text NOT NULL,
			`server_ip` varchar(100) NOT NULL,
			`client_ip` varchar(100) NOT NULL,
			`user_agent` varchar(500) NOT NULL,
			`accept_language` varchar(255) NOT NULL,
			`status` tinyint(4) NOT NULL,
			`date_added` datetime NOT NULL,
			`date_modified` datetime NOT NULL,
			PRIMARY KEY (`mpgdpr_requestlist_id`)
		) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
		");

		/*--
		-- Table structure for table `oc_mpgdpr_restrict_processing`
		--*/

		$this->db->query("CREATE TABLE IF NOT EXISTS `". DB_PREFIX ."mpgdpr_restrict_processing` (
			`mpgdpr_restrict_processing_id` int(11) NOT NULL AUTO_INCREMENT,
			`customer_id` int(11) NOT NULL,
			`store_id` int(11) NOT NULL,
			`server_ip` varchar(100) NOT NULL,
			`client_ip` varchar(100) NOT NULL,
			`user_agent` varchar(500) NOT NULL,
			`accept_language` varchar(255) NOT NULL,
			`status` tinyint(4) NOT NULL,
			`session_id` varchar(255) NOT NULL,
			`date_added` datetime NOT NULL,
			`date_modified` datetime NOT NULL,
			PRIMARY KEY (`mpgdpr_restrict_processing_id`)
		) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
		");

		/*--
		-- Table structure for table `oc_mpgdpr_upload`
		--*/

		$this->db->query("CREATE TABLE IF NOT EXISTS `". DB_PREFIX ."mpgdpr_upload` (
			`upload_id` int(11) NOT NULL AUTO_INCREMENT,
			`name` varchar(255) NOT NULL,
			`filename` varchar(255) NOT NULL,
			`code` varchar(255) NOT NULL,
			`date_added` datetime NOT NULL,
			`in_use` tinyint(1) NOT NULL,
			PRIMARY KEY (`upload_id`)
		) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
		");

		// 01-05-2022: updation start
		$this->alterTables();
		// 01-05-2022: updation end
	}
	// 01-05-2022: updation start
	public function alterTables() {
		/*13 sep 2019 gdpr session starts*/
		/*add new columns in mpgdpr_requestlist table. to pass any custom string as identified for request.*/
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "mpgdpr_requestlist` WHERE Field='custom_string'");
		if (!$query->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "mpgdpr_requestlist` ADD `custom_string` text NOT NULL AFTER `requessttype`");
		}
		/*13 sep 2019 gdpr session ends*/

		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "mpgdpr_datarequest` WHERE Field='email'");
		if (!$query->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "mpgdpr_datarequest` ADD `email` varchar(96) NOT NULL AFTER `customer_id`");
		}

		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "mpgdpr_deleteme` WHERE Field='email'");
		if (!$query->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "mpgdpr_deleteme` ADD `email` varchar(96) NOT NULL AFTER `customer_id`");
		}
	}
	// 01-05-2022: updation end
	public function anonymouse($text) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$anonymouse = '';
			$text_len = strlen($text);
			$string_len = strlen($characters);
			for ($i = 0; $i < $text_len; $i++) {
				/*13 sep 2019 gdpr session starts*/
				$anonymouse .= $characters[rand(0, $string_len-1)];
				/*13 sep 2019 gdpr session ends*/
			}
			return $anonymouse;
	}

	public function getRequestName($request_type) {
		return $this->language->get('text_'.$request_type);
	}

	public function view($route, $data=[], $template=false) {
		// front end
		if (!defined('DIR_CATALOG')) {
			return $this->isCatalogView($route, $data, $template);
		} else {
		// backend
			return $this->isAdminView($route, $data, $template=true);
		}
	}

	// remove this function
	// public function getRequestType($request_type) {
	// 	$request_code = 0;
	// 	switch ($request_type) {
	// 		case self :: CODEREQUESTDELETEME:
	// 			$request_code = 1;
	// 			break;
	// 		case self :: CODEREQUESTPERSONALDATA:
	// 			$request_code = 2;
	// 			break;
	// 		case self :: CODEDOWNLOADPERSONALINFO:
	// 			$request_code = 3;
	// 			break;
	// 		case self :: CODEDOWNLOADORDER:
	// 			$request_code = 4;
	// 			break;
	// 		case self :: CODEDOWNLOADADDRESS:
	// 			$request_code = 5;
	// 			break;
	// 		case self :: CODEDOWNLOADGDPR:
	// 			$request_code = 6;
	// 			break;
	// 		default:
	// 			$request_code = 0;
	// 			break;
	// 	}
	// 	return $request_code;
	// }
}