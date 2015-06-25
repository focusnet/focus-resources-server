<?php
 
/**
 * Application entry point.
 * 
 * 
 * 
 * This file is part of the focus-resources-server package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * FOCUS Mobile app data store
 * 
 * Error handling: if any error: just crash with appropriate HTTP resturn code. We are stateless and careless.
 * 
 * Julien Künzi
 * Yandy
 */

// FIXME my autoload + initial configuration
error_reporting(E_ALL);
ini_set('display_errors', 'on'); // FIXME DEBUG

// composer autoload;
require __DIR__ . '/contrib/autoload.php';


require_once 'includes/Configuration.inc';
require_once 'includes/Rest.inc';

// configuration
require_once 'config.php';
Configuration::getInstance()->setSettings($CONFIGURATION);

// Execution
$r = new Rest();
$r->handleRequest();

?>