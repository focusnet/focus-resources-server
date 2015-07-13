<?php 
/*
 * This file is part of the focus-resources-server package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace FocusResourcesServer;
/**
 * Configuration object
 * 
 * @author julien
 *
 */
class Configuration
{
	private $conf = array();
	
	/**
	 * Instance reference
	 */
	private static $_instance = null;
	
	/**
	 * Version information
	 */
	const APP_VERSION = '0.0.1';
	const API_VERSION = 1;
	
	/**
	 * Root uri
	 */
	private static $root_uri = FALSE;
	
	/**
	 * Empty singleton c'tor
	 */
	private function __construct() {}
	
	/**
	 * Get a reference to the Singleton instance.
	 */
	public static function getInstance()
	{
		if(is_null(self::$_instance)) {
			self::$_instance = new Configuration();
			
			// acquire root URI
			self::$_instance->root_uri = 
				$_SERVER['REQUEST_SCHEME']
				. '://' . $_SERVER['SERVER_NAME']
				. ($_SERVER['REQUEST_SCHEME'] === 'http' && $_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '')
				. ($_SERVER['REQUEST_SCHEME'] === 'https' && $_SERVER['SERVER_PORT'] != 443 ? ':' . $_SERVER['SERVER_PORT'] : '')
				. dirname($_SERVER['SCRIPT_NAME']) . '/';
		}
		return self::$_instance;
	}
	
	/**
	 * Get the root URI
	 */
	public function getRootUri()
	{
		return $this->root_uri;
	}
	
	/**
	 * Set a single setting.
	 * 
	 * @param unknown $which
	 * @param unknown $value
	 * @return unknown
	 */
	public function setSetting($which, $value)
	{
		if (is_scalar($value)) {
			$this->conf[$which] = $value;
			return $value;
		}
		Error::httpApplicationError('Configuration parameter |' . $which . '| not set to a scalar');
	}
	
	/**
	 * Set a group of settings at once. 
	 * 
	 * @param unknown $pairs
	 */
	public function setSettings($pairs)
	{
		foreach ($pairs as $which => $value) {
			self::setSetting($which, $value);
		}
	}
	
	/**
	 * Get a setting.
	 * 
	 * @param unknown $which
	 */
	public function getSetting($which, $default = NULL)
	{
		if(isset($this->conf[$which])) {
			return $this->conf[$which];
		}
		if (isset($default)) {
			return $default;
		}
		Error::httpApplicationError('Unknown configuration parameter: ' . $which);
	}
}