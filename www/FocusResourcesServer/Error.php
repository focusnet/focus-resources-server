<?php 
/**
 * HTTP Errors reporter. 
 * 
 * All these functions kill the script right away.
 * 
 * -- 
 * 
 * This file is part of the focus-resources-server package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace FocusResourcesServer;
// FIXME use exceptions? report error in body, but in json object that has a clear syntax.
class Error
{
	
	/**
	 * 400 Bad Request
	 */
	public static function httpBadRequest($msg = '')
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request', TRUE, 400);
		die($msg);
	}

	/**
	 * 403 Forbidden
	 */
	public static function httpForbidden($resource)
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', TRUE, 403);
		die('Cannot access resource: ' . $resource);
	}
	
	/**
	 * 404 Not Found
	 */
	public static function httpNotFound($resource)
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', TRUE, 404);
		die('Resource not found: ' . $resource);
	}

	/**
	 * 405 Method Not Allowed
	 * 
	 * We must specify what's allowed. 
	 * - It is only allowed to GET specific versions of a resource.
	 */
	public static function httpMethodNotAllowed($whatisallowed = array('GET'))
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed', TRUE, 405);
		header('Allow: GET');
		die();
	}
	
	/**
	 * 409 Conflict
	 */
	public static function httpConflict()
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 409 Conflict', TRUE, 409);
		die('Duplicate entry?');
	}
	
	/**
	 * 500 Internal Server Error
	 */
	public static function httpApplicationError($msg = '')
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', TRUE, 500);
		die($msg);
	}
	
	/**
	 * 501 Not Implemented
	 */
	public static function notImplemented($what = '?')
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 501 Not Implemented', TRUE, 501);
		die('Not implemented: ' . $what);
	}
	
}