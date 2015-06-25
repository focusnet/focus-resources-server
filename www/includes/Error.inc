<?php 
/*
 * This file is part of the focus-resources-server package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * Error handling
 */
class Error
{
	
	public static function httpBadRequest($msg = '')
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request', TRUE, 400);
		die($msg);
	}
	
	public static function httpForbidden($resource)
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', TRUE, 403);
		die('Cannot access resource: ' . $resource);
	}
	
	public static function httpNotFound($resource)
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', TRUE, 404);
		die('Resource not found: ' . $resource);
	}
	

	
	public static function httpMethodNotAllowed($whatisallowed = array('GET'))
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed', TRUE, 405);
		header('Allow: GET');
		die();
	}
	
	public static function httpConflict()
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 409 Conflict', TRUE, 409);
		die('Duplicate entry?');
	}
	
	public static function httpApplicationError($msg = '')
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', TRUE, 500);
		die($msg);
	}
	
	public static function notImplemented($what = '?')
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 501 Not Implemented', TRUE, 501);
		die('Not implemented: ' . $what);
	}
	
}