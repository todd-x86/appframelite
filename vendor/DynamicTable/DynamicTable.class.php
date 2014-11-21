<?php

namespace DynamicTable;

class DynamicTable
{
	protected $table;
	protected $db;
	// Relationships
	protected $rel;
	protected $pkey;
	protected $cachedSchema;
	
	// Constructor
	function __construct ($db, $table)
	{
		$this->cachedSchema = null;
		$this->pkey = null;
		$this->db = $db;
		$this->table = $table;
	}
	
	// Generates a blank query with optional SQL
	function query ($sql = null, $params = null)
	{
		$q = new Query($this->db, $this);
		
		if ($sql !== null)
		{
			$q->sql($sql, $params);
		}
		
		return $q;
	}
	
	// Adds a foreign key to the table relationship (fkey must be a string in the format 'Table.foreign_field')
	function foreignKey ($field, $fkey)
	{
		$fkey = explode('.', $fkey);
		if (!isset($this->rel[$fkey[0]]))
		{
			$this->rel[$fkey[0]] = array();
		}
		$this->rel[$fkey[0]] += [$field => $fkey[1]];
	}
	
	// Returns a Schema object for the table being represented
	function schema ()
	{
		if ($this->cachedSchema === null)
		{
			$this->cachedSchema = $this->db->schema($this->table);
		}
		return $this->cachedSchema;
	}
	
	// Return auto incremented field (or false if none)
	function autoIncrement ()
	{
		return $this->schema()->autoIncrement();
	}
	
	// Returns array of fields in primary key
	function pkey ()
	{
		if ($this->pkey === null)
		{
			$this->pkey = $this->schema()->pkey();
		}
		return $this->pkey;
	}
	
	// Returns associative array containing matching fields to join
	function relates ($table)
	{
		if (!isset($this->rel[$table->table()]))
		{
			return null;
		}
		// array('this' => 'that') -> array('that' => 'this')
		return array_flip($this->rel[$table->table()]);
	}
	
	// Returns the table name
	function table ()
	{
		return $this->table;
	}
}
