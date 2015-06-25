<?php 
/*
 * This file is part of the focus-resources-server package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
require_once 'includes/Error.inc';

/**
 * Configuration object
 * 
 * @author julien
 *
 */
class Configuration
{
	private $conf = [];
	
	/**
	 * Instance reference
	 */
	private static $_instance = null;
	
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
		}
		return self::$_instance;
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