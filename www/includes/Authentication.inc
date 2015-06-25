<?php 

/*
 * This file is part of the focus-resources-server package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
require_once 'includes/Error.inc';

/**
 * Authentication
 */

// check the header

class Authentication
{
	
	
	private $userid = FALSE;
	private $token = FALSE;
	 
	/**
	 * Instance reference
	 */
	private static $_instance = null;

	/**
	 * Empty singleton ctor
	 */
	private function __construct() {}

	/**
	 * Initialize and get the reference to the Singleton instance.
	 */
	public static function getInstance()
	{
		if(is_null(self::$_instance)) {
			self::$_instance = new Authentication();
			
			// do set some values here, based on Auth tokens in header // FIXME
			// FIXME FIXME FIXME for now fake values.
			self::$_instance->userid = '123';
			self::$_instance->token = '123456';
			
		}
		
		return self::$_instance;
	}
	
	/**
	 * Check access rights for accessing the given resource. 
	 * 
	 * @param unknown $resource
	 * @return boolean
	 */
	public function checkAccessRights($resource)
	{
		return TRUE;
	}
	
	/**
	 * Get the user ID
	 */
	public function getUserId()
	{
		return self::$_instance->userid;
	}
}