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
		
		// do not allow creating data outside of the /data/ directory
		$context = preg_replace('|^' . dirname($_SERVER['SCRIPT_NAME']) . '|', '', rtrim($_SERVER['REQUEST_URI'], '/'));
		if (preg_match('|^/data/.+$|', $context)) {
			return $this->handleDataRequest();
		}
		else {
			$matches = array();
			if (preg_match('|^/services/(.+)$|', $context, $matches)) {
				return $this->handleServiceRequest($matches[1]);
			}
		}
		Error::httpForbidden('Invalid path');
	}
	
	/**
	 * Handle service request
	 */
	private function handleServiceRequest($service)
	{
		switch($service) {
		case 'search-more-recent':
			print json_encode($this->searchMoreRecent());
			break;
		default:
			Error::httpNotFound('Service does not exist');
			break;
		}
	}
	
	/**
	 * Handle data request
	 */
	private function handleDataRequest()
	{
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
		$jsonstring_resource = $this->get($resource, $version); // FIXME for consistency, we may return an object in any case... 
		$json_resource = FALSE;
		
		switch($_SERVER['REQUEST_METHOD']) {
		case 'GET':
		case 'PUT':
		case 'DELETE':
			if (!$jsonstring_resource) {
				Error::httpNotFound($resource);
			}
			$json_resource = json_decode($jsonstring_resource);
			if ($json_resource === NULL) {
				Error::httpApplicationError('Retrieved an invalid JSON object.');
			}
			break;
		case 'POST':
			if (!!$jsonstring_resource) {
				Error::httpConflict();
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
 	private function get($resource, $version = FALSE)
 	{
 		return Database::getInstance()->get($resource, $version);
 	}
	
	/**
	 * Create a new resource.
	 * 
	 * The resource must NOT contain the version number. We always create a new sample
	 * 
	 * @param unknown $resource
	 * @param unknown $json_string
	 */
	private function post($resource, $json_string)
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
	private function put($resource, $json_old_resource, $jsonstring_new_resource)
	{
		if ($resource !== $json_old_resource->url) {
			Error::httpApplicationError('resource mismatch, something very wrong did happen.');
		}
		
		$new = json_decode($jsonstring_new_resource);
		if ($new === NULL) {
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
	private function delete($resource, $max_version = FALSE)
	{
		// check its retention policy
		
		// if only archiving
		Database::getInstance()->archive($resource, $max_version);
	}
	
	
	/**
	 * Check for newer versions of resources:
	 * 
	 * - Read the input data from the incoming POST request. It consists of a 
	 *   JSON array containing URIs of resources to check for freshness
	 *   (including version number that the remote end knows as the latest 
	 *   version).
	 *   
	 * - Return a JSON array key-value pairs in the following format:
	 *   REQUESTED-URI => <http-like status>
	 *   
	 *   	e.g.
	 *   
	 *   "http://server/data/test/1234/details/v43 => 304
	 *   
	 *   The status can be:
	 *   
	 *   status	HTTP description	expected client behavior
	 *   	
	 *   210	Content Different	client must retrieve the latest version
	 *								and update its local cache
	 *
	 *   304	Not Modified		client has nothing to do
	 *   
	 *   400	Bad Request			malformed client request. Likely required to 
	 *   							fix	some coding
	 *   
	 *   403	Forbidden			client must delete its local version (?)
	 *   
	 *   404	Not Found			client must delete its local version (the
	 *   							resource may have been deleted since the 
	 *   							last update)
	 *   
	 *   409	Conflict			The version on the client is more recent
	 *   							than the latest version on the server. The
	 *   							client should retrieve the latest version on
	 *   							the server and discard its current local 
	 *   							copy
	 */
	private function searchMoreRecent() 
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			Error::httpMethodNotAllowed(array('POST'));
		}
		
		$input = file_get_contents('php://input');
		if ($input === FALSE) {
			Error::httpBadRequest('Cannot acquire input');
		}
		
		$input = json_decode($input);
		if($input === NULL || !is_array($input)) {
			Error::httpBadRequest('Invalid JSON array input');
		}
		
		$ret = array();
		foreach ($input as $url) {
			$matches = array();
			if (!preg_match('|^(.*)/v(\d+)$|', $url, $matches)) {
				$ret[] = array($url => 400); // Bad request
				continue;
			}
			$resource = $matches[1];
			$version = $matches[2];
			
			// do we have sufficient rights to access the resource?
			// FIXME that may not be very efficient. perhaps use a service that retrieves all
			// grants at once?
			if (!Authentication::getInstance()->checkAccessRights($resource)) {
				$ret[] = array($url => '403'); // forbidden
				continue;
			}
			
			// get last version for captured resource
			$latest = Database::getInstance()->getLatestVersionNumber($resource);
			
			// 404, deleted resource?
			// FIXME if deleted, we must tell the client to delete its copy
			if ($latest === FALSE) {
				$ret[] = array($url => 404); // not found
				continue;
			}

			if ($latest > $version) {
				// more recent available.
				$ret[] = array($url => 210); // content different
				continue;
			}
			else if ($latest < $version) {
				// the provided version is more recent than the latest available. 
				// so we the client has a non-existing resource. It must update.
				$ret[] = array($url => 409); // conflict
				continue;
			}
			else {
				// same version number
				$ret[] = array($url => 304); // not modified
				continue;
			}
		}
		
		return json_encode($ret);
	}
	
	/**
	 * Get data as a bulk GET request
	 */
	private function getBulkData()
	{
		return FALSE;
	}
	
}