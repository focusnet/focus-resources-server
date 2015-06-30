<?php 
/*
 * This file is part of the focus-resources-server package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace FocusResourcesServer;

/**
 * FIXME handle version number of resources
 * 
 * http:/.../blahblah/object-343443/
 * http:/.../blahblah/object-343443/v232
 * http:/.../blahblah/object-343443/v231
 * http:/.../blahblah/object-343443/v230
 * 
 * http://server-where-the-resource-is/data/type-of-resource/resource-id/version
 * 
 * type-of-resource comes from Schema
 * resource-id is valid url identifier and depends on context. could be email address, machine ref number, etc.
 * version is v+digit
 * 
 * GET
 * POST
 * PUT
 * DELETE
 * 
 * @author julien
 *
 */



// FIXME that could be static
class Rest
{
	
	/**
	 * Handle the request.
	 */
	public function handleRequest()
	{
		isset($_SERVER['REQUEST_METHOD']) or Error::httpApplicationError('Cannot be run from the command line');
		
		// init http exchange
		header('Content-Type: application/json');
		header_remove('X-Powered-By');
		
		
		// build the full url of the requested resource.
		$resource = $_SERVER['REQUEST_SCHEME']
				. '://' . $_SERVER['SERVER_NAME']
				. ($_SERVER['REQUEST_SCHEME'] === 'http' && $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '')
				. ($_SERVER['REQUEST_SCHEME'] === 'https' && $_SERVER['SERVER_PORT'] != 443 ? ':' . $_SERVER['SERVER_PORT'] : '')
				. $_SERVER['REQUEST_URI'];
		$resource = rtrim($resource, '/');
		
		
		// FIXME we accept version-specific requests only for GET. The rest applies to last object ???? ist that correct`??
		$matches = array();
		$version = FALSE;
		if (preg_match('#^(.*)/v(\d+)$#', $resource, $matches)) {
			if (!in_array($_SERVER['REQUEST_METHOD'], array('GET', 'HEAD'))) {
				Error::httpMethodNotAllowed();
			}
			$resource = $matches[1];
			$version = $matches[2];
		}

		
		// check that the resource exists if we try to access it
		$jsonstring_resource = Database::getInstance()->get($resource, $version); // FIXME for consistency, we may return an object in any case... 
		$json_resource = FALSE;
		
		switch($_SERVER['REQUEST_METHOD']) {
		case 'GET':
		case 'PUT':
		case 'DELETE':
			if (!$jsonstring_resource) {
				Error::httpNotFound($resource);
			}
			$json_resource = json_decode($jsonstring_resource);
			if (!$json_resource) {
				Error::httpApplicationError('Retrieved an invalid JSON object.');
			}
			break;
		case 'POST':
			if (!!$jsonstring_resource) {
				Error::httpConflict(); // FIXME if version is supplied, not the same
			}
			break;
		default:
			Error::notImplemented();
			break;
		}
		
		// do we have sufficient rights to access the resource?
		if (!Authentication::getInstance()->checkAccessRights($resource)) {
			Error::httpForbidden();
			// oauth2 acccess control? wee are a resource server / as may be others that we will be accessing.
			exit;
		}

		// and fulfill the request
		switch($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			header('Content-Location: ' . $json_resource->url . '/v' . $json_resource->version);
			print $jsonstring_resource;
			break;
		case 'PUT':
			$this->put($resource, $json_resource, file_get_contents('php://input'));
			break;
		case 'DELETE':
			$this->delete($resource, $json_resource->version);
			break;
		case 'POST':
			$this->post($resource, file_get_contents('php://input'));
			break;
		default:
			Error::notImplemented();
			break;
		}
	}
	
	/**
	 * Get the requested resource.
	 * 
	 * The resource may contain the version number.
	 * 
	 * @param unknown $resource
	 */
// 	public function get($resource, $version = FALSE)
// 	{
// 		print Database::getInstance()->get($resource, $version);
// 	}
	
	/**
	 * Create a new resource.
	 * 
	 * The resource must NOT contain the version number. We always create a new sample
	 * 
	 * @param unknown $resource
	 * @param unknown $json_string
	 */
	public function post($resource, $json_string)
	{
		// augment with required data
		// == server-side contribution
		$o = (object)array();
		$o->url = $resource;
		$o->version = 1;
		$o->owner = Authentication::getInstance()->getUserId();
		$o->editor = $o->owner;
		$o->creationDateTime = date('c', time());
		$o->editionDateTime = $o->creationDateTime;
		$o->active = TRUE;
		
		$v = new Validator();
		$o = $v->validate($json_string, $o);
		$new = Database::getInstance()->create($o);
		
		if (!$new) {
			Error::httpConflict();
		}
		
		// return the newly created object with status 201
		// entity body contains the new resource,
		// Location header contains canonical path to resource
		// FIXME ??? header('Location: ' . $o->url . '/v' . $o->version, TRUE, 201);
		header('Content-Location: ' . $o->url . '/v' . $o->version, TRUE, 201);
		print $new;
		exit;		
	}
	
	/**
	 * Update an existing resource.
	 * 
	 * The resource must NOT contain the version number. We always update the last sample
	 * 
	 * @param unknown $resource
	 * @param unknown $json_string
	 */
	public function put($resource, $json_old_resource, $jsonstring_new_resource)
	{
		if ($resource !== $json_old_resource->url) {
			Error::httpApplicationError('resource mismatch, something very wrong did happen.');
		}
		
		$new = json_decode($jsonstring_new_resource);
		if (!$new) {
			Error::httpApplicationError('Resources do not match');
		}
		
		// attributes set by backend (override transmitted content)
		$o = (object)array();
		$o->url = $resource;
		$o->version = $json_old_resource->version + 1;
		
		$o->type = $json_old_resource->type;
		$o->owner = $json_old_resource->owner;
		$o->creationDateTime = $json_old_resource->creationDateTime;
		$o->editor = Authentication::getInstance()->getUserId();
		$o->editionDateTime = date('c', time());
		$o->active = TRUE;

		// FIXME TODO: check that the announced type is the same as the one of old resource!!!!
		$v = new Validator();
		$o = $v->validate($jsonstring_new_resource, $o);
		
		// best effort transaction: if a new PUT, will simply delete the last created, but that's alright.
		// FIXME but at some point active = TRUE for 2 samples !!! ==> we should NEVER rely on 'active' only to get latest version

		$new = Database::getInstance()->create($o);
		if (!$new) {
			Error::httpConflict(); // FIXME duplicate update?
		}
		$this->delete($resource, $json_old_resource->version);
		
		// return the newly created object with status 201
		// entity body contains the new resource,
		// Location header contains canonical path to resource
		// FIXME ?? header('Location: ' . $o->url . '/v' . $o->version, TRUE, 200);
		header('Content-Location: ' . $o->url . '/v' . $o->version, TRUE, 200);
		print $new;
		exit;


		// alter correct rsrc=?		
		if ($o->resource !== $resource) {
			Error::httpApplicationError('Resources do not match');
		}
		
	}
	
	/**
	 * Delete an existing resource.
	 * 
	 * The resource must NOT contain the version number: we always update the last sample up to $max_version.
	 * 
	 * @param unknown $resource
	 * @param integer max_version when starting the script, we fetched a specific element (GET) and we agree to delete up to this element. 
	 * More recent elements won't be touched. 
	 */
	public function delete($resource, $max_version = FALSE)
	{
		// check its retention policy
		
		// if only archiving
		Database::getInstance()->archive($resource, $max_version);
	}
	
	
	
}