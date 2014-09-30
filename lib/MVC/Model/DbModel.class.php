<?php

/**
 * DbModel
 *
 * Extension of the Model class with DB access methods.
 */

namespace Base\MVC\Model;
use Base\App;
use Base\MVC\Model;
use Base\Db\Filter;
use Base\Db\Result;

class DbModel extends Model
{
	// Database connection
	protected $db;
	// Table name
	protected $table;
	// Primary key
	protected $primaryKey;
	// Field order
	protected $order = null;
	
	
	// Constructor
	function __construct ()
	{
		parent::__construct();
		$this->db = App::Database($this->db);
	}
	
	// Generates a new Filter for operating on rows in the table
	function filter ($key, $value)
	{
		// Return DB Filter
		$f = new Filter($this->db, $this->table);
		return $f->filter($key, $value);
	}
	
	// Adds a new row to the table
	function add ($data)
	{
		$q = 'INSERT INTO `%s` (%s) VALUES (%s)';
		list($keys, $values, $params) = $this->parseInsertRow($data);
		$query = sprintf($q, $this->table, $keys, $values);
		$q = $this->db->sql($query);
		return $q->execute($params);
	}
	
	// Generates parts for an INSERT row from a data set
	protected function parseInsertRow ($data)
	{
		$keys = '';
		$values = '';
		$params = [];
		foreach ($data as $key => $value)
		{
			if (strlen($values) > 0)
			{
				$keys .= ',';
				$values .= ',';
			}
			$keys .= sprintf('`%s`', $key);
			if (is_array($value))
			{
				if (isset($value[0]))
				{
					// SQL Expression
					$values .= current($value);
				}
				else
				{
					// Expression with prepared segment
					$values .= key($value);
					$params = array_merge($params, current($value));
				}
			}
			else
			{
				$values .= '?';
				$params[] = $value;
			}
		}
		return [$keys, $values, $params];
	}
	
	// Removes an entry based on the primary key
	function delete ($key)
	{
		$q = $this->db->sql(sprintf('DELETE FROM `%s` WHERE `%s` = ?', $this->table, $this->primaryKey));
		return $q->execute([$key]);
	}
	
	// Returns a single row from the table matching a primary key
	function get ($id)
	{
		$q = $this->db->sql(sprintf('SELECT * FROM `%s` WHERE `%s` = ? LIMIT 1', $this->table, $this->primaryKey));
		if ($q->execute([$id]))
		{
			return new Result($q->fetch(\PDO::FETCH_ASSOC));
		}
		else
		{
			return false;
		}
	}
	
	// Sets result set data for updating
	function set ($id, $data)
	{
		$updateValues = [];
		$updates = '';
		foreach ($data as $key => $value)
		{
			if ($updates !== '')
			{
				$updates .= ',';
			}
			
			if (is_array($value))
			{
				if (isset($value[0]))
				{
					$updates .= sprintf('`%s` = %s', $key, current($value));
				}
				else
				{
					$updates .= key($value);
					$updateValues[] = current($value);
				}
			}
			else
			{
				$updates .= sprintf('`%s` = ?', $key);
				$updateValues[] = $value;
			}
		}
		$q = $this->db->sql(sprintf('UPDATE `%s` SET %s WHERE `%s` = ?', $this->table, $updates, $this->primaryKey));
		return $q->execute(array_merge($updateValues, [$id]));
	}
	
	// Sets a viewport for retrieving entries in the table
	function display ($rowCount, $page)
	{
		$this->display = [$page * $rowCount, $rowCount];
		return $this;
	}
	
	// Sets the field order for retrieving entries in the table
	function order ($key, $asc)
	{
		$this->order = [$key, $asc];
		return $this;
	}
	
	// Returns all rows in the table
	function rows ()
	{
		if ($this->display !== null)
		{
			$q = $this->db->sql(sprintf('SELECT * FROM `%s` %s LIMIT %d, %d', $this->table, $this->orderString(), $this->display[0], $this->display[1]));
		}
		else
		{
			$q = $this->db->sql(sprintf('SELECT * FROM `%s` %s', $this->table, $this->orderString()));
		}
		if ($q->execute())
		{
			return $q->fetchAll();
		}
		else
		{
			return false;
		}
	}
	
	// Returns a count of all rows in the table
	function rowCount ()
	{
		$q = $this->db->sql(sprintf('SELECT COUNT(*) as row_count FROM `%s`', $this->table));
		if ($q->execute())
		{
			return (int)$q->fetch(\PDO::FETCH_ASSOC)['row_count'];
		}
		else
		{
			return false;
		}
	}
	
	// Generates the ORDER BY clause in an SQL statement
	protected function orderString ()
	{
		if ($this->order == null)
		{
			return '';
		}
		else
		{
			return sprintf('ORDER BY `%s` %s', $this->order[0], $this->order[1]);
		}
	}
}