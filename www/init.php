<?php 


// composer autoload
$loader = require __DIR__ . '/contrib/autoload.php';

// autoload our classes - only for the FocusResourcesServer namespace
spl_autoload_register(function ($class_name)
{	
	if (strpos($class_name, 'FocusResourcesServer\\') !== 0) {
		return;
	}
	$class_path = __DIR__ . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';
	if (is_readable($class_path)) {
		require_once $class_path;
	}
});

// configuration
require_once 'config.php';
$config = FocusResourcesServer\Configuration::getInstance();
$config->setSettings($FOCUS_REST_CONFIGURATION);

// application-wide configurations
ini_set('allow_url_fopen', 'on');
date_default_timezone_set('UTC');

// init http exchange
header('Content-Type: application/json');
header_remove('X-Powered-By');

// and setup the custom headers
header('X-FOCUS-API-Version: ' . $config::API_VERSION);
header('X-FOCUS-App-Version: ' . $config::APP_VERSION);
header('X-FOCUS-App-Root: ' . $config->getRootUri());

if ($config->getSetting('DEBUG', FALSE)) {
	error_reporting(E_ALL);
	ini_set('display_errors', 'on'); // FIXME DEBUG
}
