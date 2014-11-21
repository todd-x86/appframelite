<?php

/**
 * Filter
 *
 * Provides an abstracted class for querying a database.
 */
 
namespace AFX\DbFilter;
use Base\Db;

class Filter
{
	// Database connection
	protected $db;
	// Table name
	protected $table;
	// Result set
	protected $queryData;
	
	
	// Constructor (requires a DB connection and table name)
	function __construct ($db, $table)
	{
		$this->queryData = null;
		$this->db = $db;
		$this->table = $table;
	}
	
	// Returns generated SQL for the query
	protected function getSQL ($firstRow = false, $count = false)
	{
		$sql = 'SELECT ';
		if ($count === false)
		{
			$sql .= '*';
		}
		else
		{
			$sql .= 'COUNT(*) AS row_count';
		}
		$sql .= ' FROM `'.$this->table.'`';
		if (count($this->filters) > 0)
		{
			$sql .= ' WHERE ';
			$first = true;
			foreach ($this->filters as $combo)
			{
				if (!$first)
				{
					$sql .= ' AND ';
				}
				$first = false;
				if (is_array($combo[1]))
				{
					if (isset($combo[1][0]))
					{
						$sql .= sprintf('`%s` = %s', $combo[0], $combo[1][0]);
					}
					else
					{
						$sql .= sprintf('`%s` = %s', $combo[0], key($combo[1]));
					}
				}
				else
				{
					$sql .= sprintf('`%s` = ?', $combo[0]);
				}
			}
		}
		if ($firstRow === true)
		{
			$sql .= ' LIMIT 1';
		}
		return $sql;
	}
	
	// Returns a DELETE SQL statement
	protected function getDeleteSQL ()
	{
		$sql = 'DELETE FROM `'.$this->table.'`';
		if (count($this->filters) > 0)
		{
			$sql .= ' WHERE ';
			$first = true;
			foreach ($this->filters as $combo)
			{
				if (!$first)
				{
					$sql = ' AND ';
				}
				$first = false;
				if (is_array($combo[1]))
				{
					if (isset($combo[1][0]))
					{
						$sql .= sprintf('`%s` = %s', $combo[0], $combo[1][0]);
					}
					else
					{
						$sql .= sprintf('`%s` = %s', $combo[0], key($combo[1]));
					}
				}
				else
				{
					$sql .= sprintf('`%s` = ?', $combo[0]);
				}
			}
		}
		return $sql;
	}
	
	// Returns the parameters for a prepared SQL statement
	protected function getParams ()
	{
		if (count($this->filters) > 0)
		{
			$result = array();
			foreach ($this->filters as $combo)
			{
				$value = $combo[1];
				if (is_array($value))
				{
					if (isset($value[0]))
					{
						$result[] = $value[0];
					}
					else
					{
						// Expression with prepared segment
						$result = array_merge($result, current($value));
					}
				}
				else
				{
					$result[] = $value;
				}
			}
			return $result;
		}
		else
		{
			return null;
		}
	}
	
	// Checks the first row is populated
	protected function checkFirstRow ()
	{
		if ($this->queryData === null)
		{
			// Lazy loading to prevent passing too much data over the Db connection
			$q = $this->db->sql($this->getSQL(true), Db::LAZY);
			$q->execute($this->getParams());
			
			$this->queryData = $q->fetch(\PDO::FETCH_LAZY);
		}
	}
	
	// Returns all rows from the query
	function rows ()
	{
		$q = $this->db->sql($this->getSQL(false));
		$q->execute($this->getParams());
		
		return $q->fetchAll();
	}
	
	// Returns a specific value of data from the result set
	function data ($key, $default = null)
	{
		$this->checkFirstRow();
		if (isset($this->queryData->$key))
		{
			return $this->queryData->$key;
		}
		else
		{
			return $default;
		}
	}
	
	// Returns the row count for a Filter query
	protected function getCount ()
	{
		$countSQL = $this->getSQL(false, true);
		$q = $this->db->sql($countSQL);
		$params = $this->getParams();
		
		if (is_array($params) && count($params) > 0)
		{
			$q->execute($params);
		}
		else
		{
			$q->execute();
		}
		
		$data = $q->fetch(\PDO::FETCH_ASSOC);
		if (isset($data['row_count']))
		{
			return (int)$data['row_count'];
		}
		else
		{
			return false;
		}
	}
	
	// Returns true if the row count is at least a certain number ($count)
	function min ($count)
	{
		return $this->getCount() >= $count;
	}
	
	// Removes all DB entries matched by the filter
	function clear ()
	{
		// Delete where...
		$q = $this->db->sql($this->getDeleteSQL());
		return $q->execute($this->getParams());
	}
	
	// Adds a filter to the result set
	function filter ($key, $value)
	{
		$this->filters[] = [$key, $value];
		
		return $this;
	}
}