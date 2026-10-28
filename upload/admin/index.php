<?php
// Version
define('VERSION', '3.0.3.7');

if (isset($_GET['route']) && $_GET['route'] === 'extension/module/luceed_sync/monitorAvailability') {
    define('LUCEED_MONITOR_SILENT', true);
    ini_set('display_errors', '0');
    error_reporting(E_ERROR | E_PARSE);
}

// Configuration
if (is_file('config.php')) {
	require_once('config.php');
}

// Agmedia custom Configuration
if (is_file('../env.php')) {
    require_once('../env.php');
}

// Install
if (!defined('DIR_APPLICATION')) {
	header('Location: ../install/index.php');
	exit;
}

// Startup
require_once(DIR_SYSTEM . 'startup.php');

start('admin');
