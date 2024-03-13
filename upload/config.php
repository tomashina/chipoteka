<?php
// HTTP
define('HTTP_SERVER', 'http://localhost:8888/chipoteka/upload/');

// HTTPS
define('HTTPS_SERVER', 'http://localhost:8888/chipoteka/upload/');

// DIR
define('DIR_APPLICATION', '/Applications/MAMP/htdocs/chipoteka/upload/catalog/');
define('DIR_SYSTEM', '/Applications/MAMP/htdocs/chipoteka/upload/system/');
define('DIR_IMAGE', '/Applications/MAMP/htdocs/chipoteka/upload/image/');
define('DIR_STORAGE', '/Applications/MAMP/htdocs/chipoteka/storage/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/theme/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'bakanal');
define('DB_DATABASE', 'chipoteka');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');




//cache redis

//define('CACHE_HOSTNAME', 'cache_host_name');
//define('CACHE_PORT', 'cache_host_port');
//define('CACHE_PREFIX', 'cache_host_prefix');
//define('CACHE_PASSWORD', 'cache_host_password');


define('FREESHIPPING', '66.36');
define('FREESHIPPINGB2B', '132.72');
define('RATEPRIKAZ', '132.72');