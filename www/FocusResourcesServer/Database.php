<?php 
/*
 * This file is part of the focus-resources-server package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * Database class
 * 
 * This is a singleton
 */
namespace FocusResourcesServer;

class Database
{
	/**
	 * Database connection information
	 */
	private $host = FALSE;
	private $port = FALSE;
	private $user = FALSE;
	private $pass = FALSE;
	private $dbname = FALSE;
	
	/**
	 * Connection handle
	 */
	private $mysqli = FALSE;
	
	/**
	 * Instance reference
	 */
	private static $_instance = null;
	
	/**
	 * Empty singleton c'tor
	 */
	private function __construct() {}
	
	/**
	 * Initialize and get a reference to the singleton instance.
	 */
	public static function getInstance() 
	{
		if(is_null(self::$_instance)) {
			self::$_instance = new Database();
			self::$_instance->host = Configuration::getInstance()->getSetting('db_host', 'localhost');
			self::$_instance->port = Configuration::getInstance()->getSetting('db_port', '3306');
			self::$_instance->user = Configuration::getInstance()->getSetting('db_user');
			self::$_instance->pass = Configuration::getInstance()->getSetting('db_pass');
			self::$_instance->dbname = Configuration::getInstance()->getSetting('db_dbname');
			self::$_instance->connect();
			self::$_instance->mysqli->set_charset('utf8');
		}
		return self::$_instance;
	}
	
	/**
	 * Create a connection to the database
	 */
	private function connect()
	{
		$this->mysqli = new \mysqli(
				self::$_instance->host, 
				self::$_instance->user, 
				self::$_instance->pass, 
				self::$_instance->dbname, 
				self::$_instance->port
		);
		
		// Check connection
		if ($this->mysqli->connect_error) {
			Error::httpApplicationError('Database connection error.');
		}
	}
	
	
	/**
	 * Tells whether a resource exists.
	 * 
	 * not used, would waste a SELECT
	 * 
	 * @param unknown $resource
	 * @return boolean
	 */
// 	public function exists($resource, $version = FALSE) 
// 	{
// 		return !!$this->get($resource, $version);
// 	}
	
	/**
	 * Retrieve a resource from the database.
	 * 
	 * FIXME do we allow retrieval of non-active resources? yep.
	 */
	public function get($resource, $version = FALSE)
	{
		$query = 'SELECT data FROM samples WHERE url = "' . $this->mysqli->real_escape_string($resource) . '"';
		if ($version) {
			if (!preg_match('/^\d+$/', $version)) {
				Error::httpApplicationError('Invalid version number');
			}
			$query .= ' AND version = ' . $version; 
		}
		if (!$version) {
			$query .= ' ORDER BY id DESC';
		}
		$query .= ' LIMIT 1';

		$res = $this->mysqli->query($query);
		if(!$res) { 
			Error::httpApplicationError('Database connection error.');
		}
		
		if (!($row = $res->fetch_row()) || !isset($row[0])) {
			return FALSE;
		}
		return $row[0];
	}
	
	/**
	 * Create a new resource in the database.
	 * 
	 * Everything has already been sanitized.
	 * 
	 * @param unknown $json_object
	 */
	public function create($json_object)
	{
		if (!($stmt = $this->mysqli->prepare(
				'INSERT INTO samples(url, version, type, owner, creation_datetime, editor, edition_datetime, active, data) '
				. 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'))) {
			$stmt->close();
			Error::httpApplicationError('Invalid insertion.');
		}
		
		$data = json_encode($json_object, JSON_PRETTY_PRINT); // FIXME disable pretty print
		if (!$stmt->bind_param('sisssssis', 
				$json_object->url, $json_object->version, $json_object->type, 
				$json_object->owner, $json_object->creationDateTime, 
				$json_object->editor, $json_object->editionDateTime, $json_object->active, $data)
				) {
			$stmt->close();
			Error::httpApplicationError('Invalid binding on insertion');
		}
		
		if(!$stmt->execute()) {
			// likely to be a duplicate entry
			// mmay also be because of invalid data! FIXME  so we must bulletproof user inputs
			$stmt->close();
			return FALSE;
		}
		
		if (!preg_match('/^\d+$/', $this->mysqli->insert_id)) {
			Error::httpApplicationError('Invalid insert ID');
		}
		
		$res = $this->mysqli->query('SELECT data FROM samples WHERE id = ' . $this->mysqli->insert_id . ' LIMIT 1');
		if(!$res || !($row = $res->fetch_row()) || !isset($row[0])) {
			Error::httpApplicationError('Cannot fetch inserted data.');
		}
		
		// FIXME retention policy
		
		return $row[0];
	}

	
	/**
	 * Delete an existing resource. 
	 * 
	 * @param unknown $resource
	 */
// 	public function delete($resource)
// 	{
// 		// FIXME ?? required? let's see later.
// 	}
	
	/**
	 * Archive a resource (i.e. set its 'active' field to FALSE)
	 * 
	 * FIXME archived resource has status set to 1 internally.
	 * should we remove 'active' from the JSON schema?
	 * OR should we update all objects?
	 */
	public function archive($resource, $max_version = FALSE)
	{
		$query = 'UPDATE samples SET active = FALSE WHERE url = "' . $this->mysqli->real_escape_string($resource) . '"';
		if ($max_version) {
			if (!preg_match('/^\d+$/', $max_version)) {
				Error::httpApplicationError('Invalid version');
			} 
			$query .= ' AND version <= ' . $max_version;
		}
		$res = $this->mysqli->query($query);
		if(!$res) {
			Error::httpApplicationError('Cannot archive resource.');
		}
	}
	
	// no need for transaction for now. our system is best effort.
	// however if at some point we need transactions -> move to InnoDB (MyISAM does not support transactions)
// 	/**
// 	 * Start a transaction
// 	 * 
// 	 */
// 	public function startTransaction()
// 	{
// 		$this->mysqli->begin_transaction();
// 	}
	
// 	/**
// 	 * Commit
// 	 * 
// 	 */
// 	public function commitTransaction()
// 	{
// 		$this->mysqli->commit();
// 	}
	
	
// 	/**
// 	 * Rollback
// 	 */
// 	public function rollbackTransaction()
// 	{
// 		$this->mysqli->rollback();
// 	}
}