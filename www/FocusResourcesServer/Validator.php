<?php
 
/**
 * Validator: 
 * 
 * This is the entity responsible for validating submitted data
 * before they are inserted into the database.
 * 
 * This is an extension of the JsonSchema\Uri\Retrievers\FileGetContents 
 * object that retrieves remote or local schemas. We extend it by allowing 
 * local caching of these schemas.
 *  
 * --
 * 
 * This file is part of the focus-resources-server package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FocusResourcesServer;

class Validator extends JsonSchema\Uri\Retrievers\FileGetContents implements JsonSchema\Uri\Retrievers\UriRetrieverInterface
{
	/**
	 * Constants defining the root schema. 
	 * 
	 * This will be used to allow overriding this root path for testing purpose
	 * See the DEBUG_root_schemas_url configuration setting.
	 */
	const ROOT_FOCUS_SCHEMAS = 'http://reference.focusnet.eu/schemas/';
	
	/**
	 * Check that the provided JSON string is valid against schemas 
	 * and return the corresponding object if this is the case. 
	 * 
	 * @param $override_object An object that will replace some of the properties 
	 * of the passed object, such that we can automatically update some properties
	 * such as the version number of the resource.
	 */
	public function validate($json_string, $override_object = FALSE) 
	{
		
		$object = json_decode($json_string);
		if (!isset($object)) {
			Error::httpBadRequest('Invalid JSON object.');
		}

		if ($override_object) {
			$object = (object) array_merge( (array) $object, (array) $override_object);
		}
		
		if (!isset($object->type) || !$object->type) {
			Error::httpBadRequest('Data cannot have a type');
		}
		
		// setup our custom retriever
		$retriever = new JsonSchema\Uri\UriRetriever;
		$retriever->setUriRetriever($this); // current class implements UriRetrieverInterface
		
		// retrieve the schema matching the type announced with the object.
		$schema = $retriever->retrieve($object->type);
		
		// resolve all '$ref's
		$refResolver = new JsonSchema\RefResolver($retriever);
		$refResolver->resolve($schema);
		
		// Validate
		$validator = new JsonSchema\Validator();
		$validator->check($object, $schema);
		
		if (!$validator->isValid()) {

			$ret = "JSON does not validate. Violations:\n";
			foreach ($validator->getErrors() as $error) {
				$ret .= sprintf("[%s] %s\n", $error['property'], $error['message']);
			}
			
			Error::httpBadRequest('Object does not validate against JSON schema.' . $ret);
		}
		
		return $object;
	}
	
	/**
	 * Retrieve schemas from a URI, caching them on the local filesystem.
	 * 
	 * Implements the JsonSchema retriever interface 
	 * 
	 * @param string $uri
	 */
	public function retrieve($uri) {
		$orig_uri = $uri;
		
		$alt_root_focus_schemas = Configuration::getInstance()->getSetting('DEBUG_root_schemas_url', FALSE);
		if ($alt_root_focus_schemas) {
			$uri = preg_replace('|^' . self::ROOT_FOCUS_SCHEMAS . '|', $alt_root_focus_schemas, $uri);
		}
		
		$data = FALSE;
		$filename = FALSE;
		if (!Configuration::getInstance()->getSetting('DEBUG_bypass_cache', FALSE)) {
			$filename = Configuration::getInstance()->getSetting('schemas_cache_dir', 'cache/') . sha1($orig_uri);
			if (is_readable($filename)) {
				$data = file_get_contents($uri)
					or Error::httpApplicationError('Cannot get the requested schema from local cache.');
				return json_decode($data)
					or Error::httpApplicationError('Invalid schema (not a JSON object).');
			}
		}
		
		// not cached, retrieve it
		parent::retrieve($uri);
		
		// do cache
		if ($filename) {
			file_put_contents($filename, json_encode($this->messageBody))
				or Error::httpApplicationError('Cannot write file to schemas cache directory.');
		}
		
		return $this->messageBody;
	}
	
}

