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
require_once 'init.php';
$r = new FocusResourcesServer\Rest();
$r->handleRequest();

