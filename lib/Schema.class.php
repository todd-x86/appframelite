<?php

namespace Base;

class Schema
{
	// Data type constants
	const TYPE_STRING = 0;
	const TYPE_INT = 1;
	const TYPE_FLOAT = 2;
	const TYPE_BLOB = 3;
	const TYPE_BOOL = 4;
	const TYPE_DOUBLE = 5;
	const TYPE_LONG = 6;
	const TYPE_ENUM = 7;
	const TYPE_SET = 8;
	const TYPE_DATE = 9;
	const TYPE_DATETIME = 10;
	const TYPE_TIME = 11;
	const TYPE_TIMESTAMP = 12;
	const TYPE_TEXT = 13;
	const TYPE_DECIMAL = 14;
	const TYPE_UNKNOWN = 15;
	
	// Fields
	protected $fields;
	
	
	// Constructor
	function __construct ()
	{
		$this->fields = array();
	}
	
	// Adds a new field to the schema with appropriate schema type
	function add ($field, $type)
	{
		$this->fields[$field] = [
									'type' => $type,				// Field data type
									'maxlength' => false,			// Max length of the field (false means no limit)
									'key' => false, 				// Is the field indexed as a key?
									'null' => false, 				// Can the field be null?
									'default' => false, 			// Default value (false means none set)
									'primary' => false, 			// Is it a primary key?
									'auto_increment' => false		// Does it automatically increment for the primary key?
								];
		return $this;
	}
	
	// Adds a new field to the schema from a SQL column type
	function addSqlType ($field, $type)
	{
		return $this->add($field, self::fromSqlType($type));
	}
	
	// Returns all fields in the schema
	function allFields ()
	{
		return array_keys($this->fields);
	}
	
	// Returns metadata for a field
	function get ($field)
	{
		$this->fieldCheck($field, 'Field get error');
		return $this->fields[$field];
	}
	
	// Returns the number of fields in the schema
	function count ()
	{
		return count($this->fields);
	}
	
	// Checks if a field exists
	function exists ($field)
	{
		return isset($this->fields[$field]);
	}
	
	// Override for magic method
	function __get ($field)
	{
		return $this->get($field);
	}
	
	// Override for magic method
	function __isset ($field)
	{
		return $this->exists($field);
	}
	
	// Override for magic method
	function __set ($field, $value)
	{
		throw new Exception('Schema set error', 'Schema fields cannot be updated via the "__set" magic method (use "set" instead)');
	}
	
	// Sets metadata for a field key and value
	function set ($field, $key, $value)
	{
		$this->fieldCheck($field, 'Field update error');
		
		// Keys are initialized when the field is added
		if (!isset($this->fields[$field][$key]))
		{
			throw new Exception('Field metadata error', sprintf('Attribute "%s" does not exist as part of the field metadata', $key));
		}
		
		$this->fields[$field][$key] = $value;
		return $this;
	}
	
	// Sets a field as an indexed key
	function key ($field, $is_key = true)
	{
		return $this->set($field, 'key', $is_key === true);
	}
	
	// Checks if a field exists
	protected function fieldCheck ($field, $error)
	{
		// Check if field exists
		if (!$this->exists($field))
		{
			throw new Exception($error, sprintf('Field "%s" does not exist in schema', $field));
		}
	}
	
	// Sets a field as part of the primary key 
	function primary ($field, $is_primary = true)
	{
		$this->set($field, 'primary', $is_primary === true);
		return $this->set($field, 'key', true);  // it's always a key by default
	}
	
	// Sets a field to be nullable
	function setNull ($field, $is_null = true)
	{
		return $this->set($field, 'null', $is_null === true);
	}
	
	// Sets the default value for a field
	function setDefault ($field, $default = false)
	{
		return $this->set($field, 'default', $default);
	}
	
	// Converts an SQL data type into a Schema type and length restriction
	public static function fromSqlType ($type)
	{
		$type = strtolower($type);
		if (preg_match('/^([a-zA-Z0-9]+?)(\((.+?)\)|)$/', $type, $matches))
		{
			// Field type
			$ftype = $matches[1];
			
			// Length can contain multiple parameters
			$flength = isset($matches[3]) ? explode(',', $matches[3]) : false;
			
			// Build type map and check it
			$typemap = [
							'char' => self::TYPE_STRING,
							'varchar' => self::TYPE_STRING,
							'tinytext' => self::TYPE_TEXT,
							'text' => self::TYPE_TEXT,
							'blob' => self::TYPE_BLOB,
							'mediumtext' => self::TYPE_TEXT,
							'longtext' => self::TYPE_TEXT,
							'longblob' => self::TYPE_BLOB,
							'enum' => self::TYPE_ENUM,
							'set' => self::TYPE_SET,
							'tinyint' => self::TYPE_INT,
							'smallint' => self::TYPE_INT,
							'mediumint' => self::TYPE_INT,
							'int' => self::TYPE_INT,
							'bigint' => self::TYPE_INT,
							'float' => self::TYPE_FLOAT,
							'double' => self::TYPE_DOUBLE,
							'decimal' => self::TYPE_DECIMAL,  // although most DBs treat it like a string
							'date' => self::TYPE_DATE,
							'datetime' => self::TYPE_DATETIME,
							'timestamp' => self::TYPE_TIMESTAMP,
							'time' => self::TYPE_TIME,
							'year' => self::TYPE_INT
						];
			if (isset($typemap[$ftype]))
			{
				// Bools in MySQL are represented as `tinyint(1)`
				if ($ftype == 'tinyint' && $flength[0] == '1')
				{
					return ['type' => self::TYPE_BOOL, 'length' => false];
				}
				return ['type' => $typemap[$ftype], 'length' => $flength];
			}
		}
		
		// Don't know the type
		return ['type' => self::TYPE_UNKNOWN, 'length' => false];
	}
	
	// Converts a Schema type into an SQL data type and length restriction
	public static function toSqlType ($type, $maxlen = false)
	{
		switch ($type)
		{
			case self::TYPE_STRING:
				return sprintf('varchar(%d)', (int)$maxlen);
			case self::TYPE_INT:
				return 'int';
			case self::TYPE_FLOAT:
				return 'float';
			case self::TYPE_BLOB:
				return 'blob';
			case self::TYPE_BOOL:
				return 'tinyint(1)';
			case self::TYPE_DOUBLE:
				return 'double';
			case self::TYPE_LONG:
				return 'long';
			case self::TYPE_ENUM:
				return 'enum()';
			case self::TYPE_SET:
				return 'set()';
			case self::TYPE_DATE:
				return 'date';
			case self::TYPE_DATETIME:
				return 'datetime';
			case self::TYPE_TIME:
				return 'time';
			case self::TYPE_TIMESTAMP:
				return 'timestamp';
			case self::TYPE_TEXT:
				return 'longtext';
			case self::TYPE_DECIMAL:
				return sprintf('decimal(%d,%d)', (int)$maxlen[0], (int)$maxlen[1]);
			case self::TYPE_UNKNOWN:
				return 'blob';
		}
		return null;
	}
	
	// Returns an array of primary key fields
	function pkey ()
	{
		$keys = array();
		
		// Find all keys with "primary" set to true
		if (count($this->fields) > 0)
		{
			foreach ($this->fields as $id => $metadata)
			{
				if ($metadata['primary'] === true)
				{
					array_push($keys, $id);
				}
			}
		}
		
		return $keys;
	}
	
	// Returns the auto_increment field in a table (false if none)
	function autoIncrement ()
	{
		if (count($this->fields) > 0)
		{
			foreach ($this->fields as $id => $metadata)
			{
				if ($metadata['auto_increment'] === true)
				{
					return $id;
				}
			}
		}
		
		return false;
	}
}