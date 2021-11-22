<?php
// Version
define('VERSION', '3.0.3.7');
ini_set("display_errors",1);
error_reporting(E_ALL);
// Configuration
if (is_file('config.php')) {
	require_once('config.php');
}

// Agmedia custom Configuration
if (is_file('env.php')) {
    require_once('env.php');
}

// Install
if (!defined('DIR_APPLICATION')) {
	header('Location: install/index.php');
	exit;
}

// Startup
require_once(DIR_SYSTEM . 'startup.php');

start('catalog');