<?php

/**
 * Db
 *
 * PDO wrapper class for connecting to databases and querying information.
 */

namespace Base;
use \PDO;

class Db
{
	// Access modes
	const NORMAL = 0;
	const LAZY = 1;
	
	// PDO reference
	protected $pdo;
	// Cached row count from a destructive query
	protected $rowCount = 0;
	// Database table prefix
	protected $tablePrefix = null;
	// Connection flag
	protected $connected = false;
	
	
	// Returns a database instance from a configuration file in 
	// @app/data/databases.
	public static function fromConfig ($name)
	{
		$info = parse_ini_file(App::Data('databases/'.$name.'.conf')->getFullPath());
		if (!isset($info['type']))
		{
			$type = 'mysql';
		}
		else
		{
			$type = $info['type'];
		}
		$db = new Db($info['server'], $info['username'], $info['password'], $info['database'], $type);
		if (isset($info['prefix']))
		{
			$db->setPrefix($info['prefix']);
		}
		return $db;
	}
	
	// Constructs a database with necessary connection parameters
	function __construct ($host, $user, $pass, $db, $type = 'mysql')
	{
		$connString = sprintf('%s:host=%s;dbname=%s', $type, $host, $db);
		try
		{
			$this->pdo = new PDO($connString, $user, $pass);
			$this->connected = true;
		}
		catch (\PDOException $e)
		{
			$this->connected = false;
		}
	}
	
	// Returns true if connected
	function connected ()
	{
		return $this->connected;
	}
	
	// Sets the table prefix
	function setPrefix ($p)
	{
		$this->tablePrefix = $p;
	}
	
	// Counts the rows affected by a destructive query (UPDATE, DELETE)
	function rowsAffected ()
	{
		return $this->rowCount;
	}
	
	// Returns a prepared PDOStatement object for a SQL query
	function sql ($sql, $fetchMode = self::NORMAL)
	{
		if ($this->tablePrefix !== null)
		{
			$sql = preg_replace('/\[(.+?)\]/', $this->tablePrefix.'$1', $sql);
		}
		$stmt = $this->pdo->prepare($sql);
		switch ($fetchMode)
		{
			case self::LAZY:
				$stmt->setFetchMode(PDO::FETCH_LAZY);
				break;
			case self::NORMAL:
			default:
				$stmt->setFetchMode(PDO::FETCH_ASSOC);
		}
		
		return $stmt;
	}
	
	// Executes a prepared SQL query (string) with arguments and
	// returns all data associated with the query as an array
	function query ($sql, $args = null)
	{
		if (is_array($args) && count($args) > 0)
		{
			$s = $this->sql($sql);
			$s->execute($args);
		}
		else
		{
			$s = $this->sql($sql);
			$s->execute();
		}
		return $s->fetchAll();
	}
	
	// Executes a prepared SQL statement (string) with arguments and
	// returns true if successful
	function execute ($sql, $args = null)
	{
		if (is_array($args) && count($args) > 0)
		{
			$s = $this->sql($sql);
			$result = $s->execute($args);
		}
		else
		{
			$s = $this->sql($sql);
			$result = $s->execute();
		}
		$this->rows = $s->rowCount();
		return $result;
	}
}