<?php 
/*
 * This file is part of the focus-resources-server package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * FIXME recursive validation of schemas with DataStore object?
 * How does it work?
 * 
 * 
 * @author julien
 *
 */


class Validator extends JsonSchema\Uri\Retrievers\FileGetContents implements JsonSchema\Uri\Retrievers\UriRetrieverInterface
{
	/**
	 * Root schema
	 */
	public static $ROOT_FOCUS_SCHEMAS = 'http://reference.focusnet.eu/schemas/';
	public static $FOCUS_OBJECT_SCHEMA_VERSION = 0.1;
	
	/**
	 * Check that the provided JSON string is valid against schemas 
	 * and return the corresponding object if this is the case. 
	 * 
	 * @param unknown $json_string
	 * @param string $override_object
	 * @return StdClass
	 */
	public function validate($json_string, $override_object = FALSE) 
	{
		
		$object = json_decode($json_string);
		isset($object)
			or Error::httpBadRequest('Invalid JSON object.');

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

			echo "JSON does not validate. Violations:\n";
			foreach ($validator->getErrors() as $error) {
				echo sprintf("[%s] %s\n", $error['property'], $error['message']);
			}
			
			Error::httpBadRequest('Object does not validate against JSON schema.');
		}
		
		return $object;
	}
	
	/**
	 * Implement the JsonSchema retriever interface 
	 * 
	 * This enabled local caching of schema files.
	 * 
	 * @param unknown $uri
	 */
	public function retrieve($uri) {
		$orig_uri = $uri;
		
		$alt_root_focus_schemas = Configuration::getInstance()->getSetting('DEBUG_root_schemas_url', FALSE);
		if ($alt_root_focus_schemas) {
			$uri = preg_replace('|^' . self::$ROOT_FOCUS_SCHEMAS . '|', $alt_root_focus_schemas, $uri);
		}
		
		$data = FALSE;
		$filename = FALSE;
		if (!Configuration::getInstance()->getSetting('DEBUG_bypass_cache', FALSE)) {
			$filename = Configuration::getInstance()->getSetting('schemas_cache_dir', 'cache/') . sha1($orig_uri);
			var_dump($filename);
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
	//	var_dump($this->messageBody);
		
		return $this->messageBody;
	}
	
}

