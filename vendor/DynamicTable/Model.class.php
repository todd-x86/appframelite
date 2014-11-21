<?php

namespace DynamicTable;
use Base\App;
use Base\MVC\Model\DbModel;
use Base\Validator;

class Model extends DbModel
{
	// Override $db
	// Override $table
	
	protected $findBy = null;
	
	// Constructor
	function __construct ()
	{
		$this->db = App::Database($this->db);
		$this->table = new DynamicTable($this->db, $this->table);
	}
	
	// Assigns finder to match an ID based on primary key
	function find ($id)
	{
		if (!is_array($id))
		{
			$id = array($id);
		}
		return $this->findBy($this->pkey(), $id);
	}
	
	protected function findValue ()
	{
		return $this->findBy[1];
	}
	
	// Return first row of a result from finder
	function first ($field = null)
	{
		return $this->generateQuery()->result($field);
	}
	
	// Returns generated "finder" query
	protected function generateQuery ()
	{
		$q = $this->query();
		if ($this->findBy !== null)
		{
			$q->where($this->findBy[0], $this->findBy[1]);
		}
		return $q;
	}
	
	// Assign finder to key and value for lookup
	function findBy ($key, $value)
	{
		if ($key === null || $value === null || $key === false)
		{
			$this->findBy = null;
		}
		else
		{
			$this->findBy = array($key, $value);
		}
		return $this;
	}
	
	// Return model's table's primary key
	function pkey ()
	{
		return $this->table->pkey();
	}
	
	// Return validator for a set of data
	function validate ($data)
	{
		return Validator::evaluate($data);
	}
	
	// Update row matched by finder
	function update ($data)
	{
		return $this->generateQuery()->update($data);
	}
	
	// Deletes rows matched by finder
	function delete ()
	{
		return $this->generateQuery()->delete();
	}
	
	// Inserts new row of data and sets it in finder
	function insert ($data)
	{
		$r = $this->query()->insert($data);
		if ($r === true)
		{
			$this->findBy($this->table->autoIncrement(), $this->db->lastId);
		}
		return $r;
	}
	
	// Returns a DynamicQuery
	function query ($table = null)
	{
		if ($table !== null)
		{
			$dt = new DynamicTable($this->db, $table);
			return $dt->query();
		}
		return $this->table->query();
	}
}