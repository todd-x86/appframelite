<?php

namespace DynamicTable;
use Base\Schema;
use Base\Arr;

class Query
{
	protected $query;
	protected $queryParams;
	protected $info;
	protected $table;
	protected $db;
	protected $selectFields;
	protected $checkSchema = true;
	protected $cache = true;
	protected $cacheResult;
	
	// Constructor
	function __construct ($db, $table)
	{
		$this->selectFields = [];
		$this->db = $db;
		$this->table = $table;
		$this->reset();
	}
	
	function alias ($a)
	{
		if ($a === null)
		{
			if (isset($this->info['alias']))
			{
				unset($this->info['alias']);
			}
		}
		else
		{
			$this->info['alias'] = $a;
		}
		return $this;
	}
	
	protected function getAliasSegment ()
	{
		if (isset($this->info['alias']))
		{
			return ' as `'.$this->info['alias'].'`';
		}
		else
		{
			return '';
		}
	}
	
	function from ($table, $query = null)
	{
		if ($table === null)
		{
			if (isset($this->info['from']))
			{
				unset($this->info['from']);
			}
		}
		else
		{
			$this->info['from'] = $table;
		}
		if ($query !== null)
		{
			$this->info['from_set'] = $query;
		}
		elseif (isset($this->info['from_set']))
		{
			unset($this->info['from_set']);
		}
		return $this;
	}
	
	// Resets query information
	function reset ()
	{
		$this->info = [];
		$this->query = null;
		$this->queryParams = null;
		return $this;
	}
	
	// Assigns SQL to a query (ignores query builder)
	function sql ($q, $params = null)
	{
		$this->query = $q;
		if ($params !== null)
		{
			$this->queryParams = $params;
		}
		return $this;
	}
	
	// Selects fields for table query
	// NOTE: select(null) or select(false) will clear the selector
	function select ()
	{
		$args = func_get_args();
		if (count($args) == 0 && isset($this->info['select']))
		{
			$this->selectFields = [];
			unset($this->info['select']);
		}
		elseif (count($args) == 1)
		{
			if (is_array($args[0]))
			{
				// If non-associative, implode by comma
				if (array_keys($args[0]) === range(0, count($args[0])-1))
				{
					$sel = array_map(function ($item) {
						$words = explode(' ', $item);
						return array_pop($words);
					}, $args[0]);
					$this->selectFields = array_merge($this->selectFields, $sel);
					
					// Add quotes to keywords
					$args[0] = array_map(function ($item) {
						if ($item !== '*' && strpos($item, ' ') === false && strpos($item, '.') === false)
						{
							return '`'.$item.'`';
						}
						else
						{
							return $item;
						}
					}, $args[0]);
					
					if (isset($this->info['select']))
					{
						$this->info['select'] .= ','.implode(',', $args[0]);
					}
					else
					{
						$this->info['select'] = implode(',', $args[0]);
					}
				}
				else
				{
					// Otherwise write SQL aliases out
					$this->selectFields = array_merge($this->selectFields, array_values($args[0]));
					if (!isset($this->info['select']))
					{
						$this->info['select'] = '';
					}
					
					foreach ($args[0] as $field => $alias)
					{
						if (strlen($this->info['select']) > 0)
						{
							$this->info['select'] .= ',';
						}
						
						// TODO: Check to see if can add quotes to $field
						
						if ($alias === true)
						{
							$this->info['select'] .= $field;
						}
						else
						{
							$this->info['select'] .= sprintf('%s as %s', $field, $alias);
						}
					}
				}
			}
			elseif (is_string($args[0]))
			{
				$word_list = explode(' ', $args[0]);
				$this->selectFields[] = array_pop($word_list);
				
				// TODO: Check to see if can add quotes to $field
				
				if (isset($this->info['select']))
				{
					$this->info['select'] .= ','.$args[0];
				}
				else
				{
					$this->info['select'] = $args[0];
				}
			}
			elseif (($args[0] === null || $args[0] === false) && isset($this->info['select']))
			{
				$this->selectFields = [];
				unset($this->info['select']);
			}
			elseif (is_int($args[0]) || is_float($args[0]))
			{
				$this->selectFields[] = strval($args[0]);
				if (isset($this->info['select']))
				{
					$this->info['select'] .= ','.strval($args[0]);
				}
				else
				{
					$this->info['select'] = strval($args[0]);
				}
			}
			elseif (is_callable($args[0]))
			{
				// Collect params from the select closure
				$selector = new Selector();
				call_user_func($args[0], $selector);
				$this->selectFields = array_merge($this->selectFields, $selector->__fields());
				if (isset($this->info['select']))
				{
					$this->info['select'] .= ','.$selector->__toString();
				}
				else
				{
					$this->info['select'] = $selector->__toString();
				}
			}
			elseif ($args[0] instanceof Expr)
			{
				$this->selectFields[] = $args[0]->toSql();
				if (isset($this->info['select']))
				{
					$this->info['select'] .= ','.$args[0]->toSql();
				}
				else
				{
					$this->info['select'] = $args[0]->toSql();
				}
				$params = $args[0]->getParams();
				if (is_array($params) && count($params) > 0)
				{
					$this->queryParams = array_merge($this->queryParams, $params);
				}
			}
		}
		else
		{
			$sel = array_map(function ($item) {
				$words = explode(' ', $item);
				return array_pop($words);
			}, $args);
			
			$this->selectFields = array_merge($this->selectFields, $sel);
			
			// Add quotes to keywords
			$args = array_map(function ($item) {
				if ($item !== '*' && strpos($item, ' ') === false && strpos($item, '.') === false)
				{
					return '`'.$item.'`';
				}
				else
				{
					return $item;
				}
			}, $args);
			
			if (isset($this->info['select']))
			{
				$this->info['select'] .= ','.implode(',', $args);
			}
			else
			{
				$this->info['select'] = implode(',', $args);
			}
		}
		
		return $this;
	}
	
	function selectAs ($key, $alias)
	{
		return $this->select([$key => $alias]);
	}
	
	// Selector helpers
	function sum ($var)
	{
		return $this->select(sprintf('sum(%s)', $var));
	}
	
	// Builds a condition string for a JOIN based on table relationship
	protected function getJoinConditionString ($table)
	{
		$c = $table->relates($this->table->table());
		if ($c === null)
		{
			throw new \Base\Exception('Join condition exception', 'Cannot join two tables where a foreign key relationship does not exist');
		}
		
		$result = '';
		foreach ($c as $field => $fkey)
		{
			if (strlen($result) > 0)
			{
				$result .= ' AND ';
			}
			$result .= sprintf('`%s`.%s = `%s`.%s', $this->table->table(), $field, $table->table(), $fkey);
		}
		
		return $result;
	}
	
	// Performs a join operation
	function join ($table, $condition = null, $type = null)
	{
		if ($table instanceof DynamicTable)
		{
			if ($condition === null)
			{
				$condition = $this->getJoinConditionString($table);
			}
			$table = $table->table();
		}
		elseif (!is_string($table))
		{
			// Leave if it isn't a string
			return $this; 
		}
		
		if (!isset($this->info['join']))
		{
			$this->info['join'] = '';
		}
		else
		{
			$this->info['join'] .= ' ';
		}
		
		$this->info['join'] .= sprintf('%s join `%s` on %s', $type, $table, $condition);
		
		return $this;
	}
	
	// Left join helper
	function leftJoin ($table, $condition = null)
	{
		return $this->join($table, $condition, 'left');
	}
	
	// Inner join helper
	function innerJoin ($table, $condition = null)
	{
		return $this->join($table, $condition, 'inner');
	}
	
	// Parse a template string and return prepared SQL with set of parameters
	protected function parseTemplateString ($tpl, $params)
	{
		$result_params = [];
		
		// Find any template parameters and add them (typecasted) to a result for passing to $query_params
		if (preg_match_all('/\{(.+?)\}/', $tpl, $fields) && isset($fields[1]))
		{
			$fields = $fields[1];
			foreach ($fields as $name)
			{
				$tpl_field = explode(':', $name);
				if (count($tpl_field) == 1)
				{
					// No typecasting
					if (isset($params[$name]))
					{
						array_push($result_params, $params[$name]);
					}
					else
					{
						// Push null if it doesn't exist
						array_push($result_params, null);
					}
				}
				else
				{
					// Typecast
					$name = $tpl_field[1];
					if (isset($params[$name]))
					{
						$value = $params[$name];
						
						switch (trim(strtolower($tpl_field[0])))
						{
							case 'i':
								array_push($result_params, intval($value));
								break;
							case 'f':
								array_push($result_params, floatval($value));
								break;
							case 's':
								array_push($result_params, strval($value));
								break;
							default:
								array_push($result_params, $value);
						}
					}
					else
					{
						// Push null if it doesn't exist
						array_push($result_params, null);
					}
				}
			}
			$tpl = preg_replace('/\{(.+?)\}/', '?', $tpl);
		}
		return [$tpl, $result_params];
	}
	
	// Parse where condition arguments and operator
	protected function parseWhere ($args, $operator = null)
	{
		if (count($args) == 1)
		{
			if (is_array($args[0]))
			{
				if (array_keys($args[0]) === range(0, count($args[0])-1))
				{
					// where(array(1,2,3,4))
					$this->parseWhere([$this->table->pkey()[0], 'in', $args[0]], $operator);
				}
				else
				{
					// where(array('x' => 5, 'y' => 4))
					$this->parseWhere([array_keys($args[0]), array_values($args[0])], $operator);
				}
			}
			elseif (is_string($args[0]))
			{
				// where('x = 5 and y = 4')
				if (!isset($this->info['where']))
				{
					$this->info['where'] = $operator.' '.$args[0];
				}
				else
				{
					$this->info['where'] .= ' '.$operator.' '.$args[0];
				}
			}
			elseif ($args[0] instanceof Expr)
			{
				$this->parseWhere([$args[0]->toSql()], $operator);
				$params = $args[0]->getParams();
				if (is_array($params) && count($params) > 0)
				{
					$this->queryParams = array_merge($this->queryParams, $params);
				}
			}
			else
			{
				throw new \Base\Exception('Unknown Condition', 'Cannot evaluate single-argument where condition that is not an array or string');
			}
		}
		elseif (count($args) == 2)
		{
			if (is_array($args[0]) && is_array($args[1]))
			{
				// where(array('x', 'y'), array(5, 4))
				if (count($args[0]) != count($args[1]))
				{
					throw new \Base\Exception('Key-Value Mismatch', 'The number of keys (columns) in the where condition does not match the number of values provided');
				}
				$keys = $args[0];
				$values = $args[1];
				while (count($values) > 0)
				{
					$this->parseWhere([array_shift($keys), array_shift($values)], $operator);
					$operator = 'AND';
				}
			}
			/*elseif (is_string($args[0]) && is_array($args[1]))
			{
				// where('x = {i:x} and y = {i:y}', array('x' => 5, 'y' => 4))
				list($condition, $params) = $this->parseTemplateString($args[0], $args[1]);
				$this->parseWhere([$condition], $operator);
				
				// Merge parameters
				$this->queryParams = array_merge($this->queryParams, $params);
				
			}*/
			else
			{
				// where('x', '5')
				if ($args[1] === null)
				{
					$this->parseWhere([$args[0], 'is', null], $operator);
				}
				else
				{
					$this->parseWhere([$args[0], '=', $args[1]], $operator);
				}
			}
			
		}
		elseif (count($args) == 3)
		{
			// Accounts for "like" and "not like"
			if (strcasecmp(substr($args[1], -4), 'like') === 0 && substr($args[2], 0, 1) != '%' && substr($args[2], -1) != '%')
			{
				// where('x', 'like', 'foo')
				$this->parseWhere([$args[0], $args[1], '%'.$args[2].'%'], $operator);
			}
			elseif (strcasecmp(substr($args[1], -2), 'is') === 0)
			{
				// where('x', 'is', null)
				if ($args[2] === null)
				{
					$args[2] = 'null';
				}
				$condition = sprintf('%s is %s', $args[0], $args[2]);
				$this->parseWhere([$condition], $operator);
			}
			elseif (strcasecmp(substr($args[1], -2), 'in') === 0)
			{
				if (is_array($args[2]))
				{
					// where('x', 'in', array('foo', 'bar'))
					if ($this->queryParams === null)
					{
						$this->queryParams = $args[2];
					}
					else
					{
						$this->queryParams = array_merge($this->queryParams, $args[2]);
					}
					$param_str = substr(str_repeat('?,', count($args[2])), 0, -1);
					$condition = sprintf('%s in (%s)', $args[0], $param_str);
					$this->parseWhere([$condition], $operator);
				}
				elseif ($args[2] instanceof Query)
				{
					// where('x', 'in', $select_query2)
					if ($this->queryParams === null)
					{
						$this->queryParams = $args[2]->getParams();
					}
					else
					{
						$this->queryParams = array_merge($this->queryParams, $args[2]->getParams());
					}
					
					$inner_query = $args[2]->toSql();
					$condition = sprintf('%s in (%s)', $args[0], $inner_query);
					$this->parseWhere([$condition], $operator);
				}
			}
			else
			{
				// where('x', '=', '5')
				if (is_array($args[2]))
				{
					// where('x', '=', ['MD5(?)' => [$arg1]])
					list($funcArg, $params) = $this->parseClosure($args[2]);
					
					$condition = sprintf('%s %s %s', $args[0], $args[1], $funcArg);
					if ($params != null)
					{
						if ($this->queryParams === null)
						{
							$this->queryParams = $params;
						}
						else
						{
							$this->queryParams = array_merge($this->queryParams, $params);
						}
					}
					$this->parseWhere([$condition], $operator);
				}
				else
				{
					$condition = sprintf('%s %s ?', $args[0], $args[1]);
					if ($this->queryParams === null)
					{
						$this->queryParams = [$args[2]];
					}
					else
					{
						$this->queryParams[] = $args[2];
					}
					$this->parseWhere([$condition], $operator);
				}
			}
		}
		elseif (count($args) > 3)
		{
			// Mainly used for functions where arguments are sanitized separately
			// where('lower(email)', '=', 'lower(', $email, ')')
			// where('x = 5', 'and', '(y = 4', 'or', 'y = 8)')
			// where('x', 'in', array(1,2,3), 'and', 'y', '=', 4);
			$values = $args;
			$condition = '';
			while (count($values) > 0)
			{
				$part = array_shift($values);
				$condition .= ' '.$part;
				if (substr($part, -1) == '(')
				{
					if ($this->queryParams === null)
					{
						$this->queryParams = [array_shift($values)];
					}
					else
					{
						$this->queryParams[] = array_shift($values);
					}
					$condition .= ' ?';
				}
			}
			
			// Remove first space
			$condition = substr($condition, 1);
			$this->parseWhere([$condition], $operator);
		}
	}
	
	// Where condition method
	function where ()
	{
		$this->parseWhere(func_get_args());
		return $this;
	}
	
	function andWhere ()
	{
		$this->parseWhere(func_get_args(), 'and');
		return $this;
	}
	
	function orWhere ()
	{
		$this->parseWhere(func_get_args(), 'or');
		return $this;
	}
	
	// Group by helper
	function groupBy ()
	{
		$seg = implode(',', func_get_args());
		if (!isset($this->info['group_by']))
		{
			$this->info['group_by'] = $seg;
		}
		else
		{
			$this->info['group_by'] .= ','.$seg;
		}
		return $this;
	}
	
	// Order by helper
	function orderBy ()
	{
		$args = func_get_args();
		if (count($args) == 1)
		{
			if (is_array($args[0]))
			{
				foreach ($args[0] as $key => $traverse)
				{
					$this->orderBy($key, $traverse);
				}
			}
			else
			{
				$this->orderBy($args[0], 'asc');
			}
		}
		elseif (count($args) == 2)
		{
			if (!isset($this->info['order_by']))
			{
				$this->info['order_by'] = $args[0].' '.$args[1];
			}
			else
			{
				$this->info['order_by'] .= ','.$args[0].' '.$args[1];
			}
		}
		return $this;
	}
	
	// Limit helper
	function limit ($low, $high = false)
	{
		if ($low === false)
		{
			if (isset($this->info['limit']))
			{
				unset($this->info['limit']);
			}
		}
		elseif ($high === false)
		{
			$this->info['limit'] = strval($low);
		}
		else
		{
			$this->info['limit'] = sprintf('%d,%d', $low, $high);
		}
		
		return $this;
	}
	
	// Generates a query from the $info variable
	protected function buildQuery ()
	{
		$query = 'SELECT ';
		
		// Add select fields
		if (isset($this->info['select']))
		{
			$query .= $this->info['select'];
		}
		else
		{
			$query .= '*';
		}
		
		// If nested subset is defined
		if (isset($this->info['from']))
		{
			$tbl = $this->info['from'];
			
			// Subset
			if (isset($this->info['from_set']))
			{
				if ($this->info['from_set'] instanceof Query)
				{
					$sql = $this->info['from_set']->toSql();
					if ($this->queryParams === null)
					{
						$this->queryParams = $this->info['from_set']->getParams();
					}
					else
					{
						// NOTE: Correct this eventually (separate select params and where params in query_params)
						$this->queryParams = array_merge($this->queryParams, $this->info['from_set']->getParams());
					}
				}
				else
				{
					$sql = $this->info['from_set'];
				}
				$query .= sprintf(' FROM (%s) %s', $sql, $tbl);
			}
			else
			{
				$query .= sprintf(' FROM `%s`%s', $tbl, $this->getAliasSegment());
			}
		}
		else
		{
			$query .= sprintf(' FROM `%s`%s', $this->table->table(), $this->getAliasSegment());
		}
		
		// Add Joins
		if (isset($this->info['join']))
		{
			$query .= ' '.$this->info['join'];
		}
		
		// Where conditions
		if (isset($this->info['where']))
		{
			$query .= ' WHERE '.$this->info['where'];
		}
		
		// Group by
		if (isset($this->info['group_by']))
		{
			$query .= ' GROUP BY '.$this->info['group_by'];
		}
		
		// Order
		if (isset($this->info['order_by']))
		{
			$query .= ' ORDER BY '.$this->info['order_by'];
		}
		
		// Limit
		if (isset($this->info['limit']))
		{
			$query .= ' LIMIT '.$this->info['limit'];
		}
		
		return $query;
	}
	
	// Returns SQL from query
	function toSql ()
	{
		return $this->getQuery();
	}
	
	function getParams ()
	{
		return $this->queryParams;
	}
	
	// Returns query for generating results
	protected function getQuery ()
	{
		if ($this->query !== null)
		{
			return $this->query;
		}
		else
		{
			return $this->buildQuery();
		}
	}
	
	protected function parseClosure ($c)
	{
		if (isset($c[0]))
		{
			// ['NOW()']
			return [$c[0], null];
		}
		else
		{
			// ['MD5(?)' => [$value]]
			return [key($c), current($c)];
		}
	}
	
	protected function initInsert ($row)
	{
		$keys = '';
		$values = '';
		$this->queryParams = [];
		
		if ($this->checkSchema)
		{
			// Intersect fields in schema
			$fields = $this->table->schema()->allFields();
			$row = array_intersect_key($row, array_flip($fields));
		}
		
		foreach ($row as $key => $value)
		{
			$keys .= '`'.$key.'`,';
			if ($value instanceof Expr)
			{
				$values .= $value->toSql().',';
				$params = $value->getParams();
				if (is_array($params) && count($params) > 0)
				{
					$this->queryParams = array_merge($this->queryParams, $params);
				}
			}
			elseif ($value instanceof Query)
			{
				$values .= '('.$value->toSql().'),';
				$params = $value->getParams();
				if (is_array($params) && count($params) > 0)
				{
					$this->queryParams = array_merge($this->queryParams, $params);
				}
			}
			elseif (is_array($value))
			{
				list($key, $params) = $this->parseClosure($value);
				$values .= $key.',';
				if ($params !== null)
				{
					$this->queryParams = array_merge($this->queryParams, $params);
				}
			}
			else
			{
				$values .= '?,';
				$this->queryParams[] = $value;
			}
		}
		return [$keys, $values];
	}
	
	// Insert
	function insert ($row)
	{
		if (!is_array($row) || count($row) < 1)
		{
			return true;
		}
		list($keys, $values) = $this->initInsert($row);
		$query = sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $this->table->table(), substr($keys, 0, -1), substr($values, 0, -1));

		return $this->db->execute($query, $this->queryParams);
	}
	
	function replaceInto ($row)
	{
		if (!is_array($row) || count($row) < 1)
		{
			return true;
		}
		list($keys, $values) = $this->initInsert($row);
		$query = sprintf('REPLACE INTO `%s` (%s) VALUES (%s)', $this->table->table(), substr($keys, 0, -1), substr($values, 0, -1));

		return $this->db->execute($query, $this->queryParams);
	}
	
	// Insert with SELECT instead of VALUES
	function insertSelect ($row)
	{
		$keys = '';
		$values = '';
		$this->queryParams = [];
		
		if ($this->checkSchema)
		{
			// Intersect fields in schema
			$fields = $this->table->schema()->allFields();
			$row = array_intersect_key($row, array_flip($fields));
		}
		
		foreach ($row as $key => $value)
		{
			$keys .= '`'.$key.'`,';
			if ($value instanceof Expr)
			{
				$values .= $value->toSql().',';
				$params = $value->getParams();
				if (is_array($params) && count($params) > 0)
				{
					$this->queryParams = array_merge($this->queryParams, $params);
				}
			}
			elseif ($value instanceof Query)
			{
				$values .= '('.$value->toSql().'),';
				$params = $value->getParams();
				if (is_array($params) && count($params) > 0)
				{
					$this->queryParams = array_merge($this->queryParams, $params);
				}
			}
			else
			{
				$values .= '?,';
				$this->queryParams[] = $value;
			}
		}
		$query = sprintf('INSERT INTO `%s` (%s) SELECT %s FROM `%s`', $this->table->table(), substr($keys, 0, -1), substr($values, 0, -1), $this->table->table());
		return $this->db->execute($query, $this->queryParams);
	}
	
	function lastInsertId ()
	{
		return $this->db->lastId;
	}
	
	// Delete
	function delete ()
	{
		$query = sprintf('DELETE FROM `%s`', $this->table->table());
		
		// Where conditions
		if (isset($this->info['where']))
		{
			$query .= ' WHERE '.$this->info['where'];
		}
		
		// Order
		if (isset($this->info['order_by']))
		{
			$query .= ' ORDER BY '.$this->info['order_by'];
		}
		
		// Limit
		if (isset($this->info['limit']))
		{
			$query .= ' LIMIT '.$this->info['limit'];
		}
		
		return $this->db->execute($query, $this->queryParams);
	}
	
	// Update
	function update ($cols)
	{
		$updates = '';
		
		if (!isset($this->queryParams))
		{
			$this->queryParams = [];
		}
		
		if ($this->checkSchema)
		{
			// Intersect fields in schema
			$fields = $this->table->schema()->allFields();
			$cols = array_intersect_key($cols, array_flip($fields));
		}
		
		// Update strings
		$params = [];
		
		// Nothing to update
		if (!is_array($cols) || count($cols) < 1)
		{
			return true;
		}
		
		foreach ($cols as $key => $value)
		{
			if ($value instanceof Expr)
			{
				$tpl_value = $value->toSql();
				$qparams = $value->getParams();
				if (is_array($qparams) && count($qparams) > 0)
				{
					$params = array_merge($params, $qparams);
				}
			}
			else
			{
				$tpl_value = '?';
				$params[] = $value;
			}
			$updates .= sprintf('`%s` = %s,', $key, $tpl_value);
		}
		
		$this->queryParams = array_merge($params, $this->queryParams);
		
		$query = sprintf('UPDATE `%s` SET %s', $this->table->table(), substr($updates, 0, -1));
		
		// Where conditions
		if (isset($this->info['where']))
		{
			$query .= ' WHERE '.$this->info['where'];
		}
		
		// Limit
		if (isset($this->info['limit']))
		{
			$query .= ' LIMIT '.$this->info['limit'];
		}
		
		return $this->db->execute($query, $this->queryParams);
	}
	
	// Return count for number of rows matched by query (without limits)
	function totalCount ($reset = true)
	{
		$cache = $this->cache;
		$this->cache = false;
		// Preserve old select
		if (isset($this->info['select']))
		{
			$sel = $this->info['select'];
		}
		$this->select(['count(*)' => 'row_count']);
		
		// Erase the limit
		if (isset($this->info['limit']))
		{
			$limit = $this->info['limit'];
			unset($this->info['limit']);
		}
		
		// Erase the order by
		if (isset($this->info['order_by']))
		{
			$order = $this->info['order_by'];
			unset($this->info['order_by']);
		}
		
		if (isset($this->info['group_by']))
		{
			// Count the 'counts' ("Escape from Alcatraz" [1973])
			$q = new Query($this->db, null);
			$q->from('x', $this);
			$q->selectAs('count(*)', 'row_count');
			$result = $q->result();
		}
		else
		{
			$result = $this->result();
			if ($reset)
			{
				$this->reset();
			}
		}
		
		// Add it back
		if (isset($limit))
		{
			$this->info['limit'] = $limit;
		}
		if (isset($order))
		{
			$this->info['order_by'] = $order;
		}
		
		if (isset($sel))
		{
			$this->info['select'] = $sel;
		}
		else
		{
			unset($this->info['select']);
		}
		$this->cache = $cache;
		
		if ($result === false)
		{
			return false;
		}
		else
		{
			return $result['row_count'];
		}
	}
	
	// Return count for number of rows matched by query
	function count ($reset = true)
	{
		return $this->totalCount($reset);
	}
	
	// Returns columns selected
	function columns ()
	{
		$selected = $this->selectFields;
		if (count($selected) == 0)
		{
			// None selected indicates that all fields will be returned
			return $this->table->schema()->allFields();
		}
		elseif (isset($selected['*']))
		{
			// * indicates selecting all fields + any specified fields
			unset($selected['*']);
			return array_unique(array_merge($selected, $this->table->schema()->allFields()));
		}
		else
		{
			// NOTE: If the user enters a field twice (by mistake or for whatever reason)
			//       SQL DB will return only one value but will still accept it despite
			//       duplicate field selection (I think)
			return array_unique($selected);
		}
	}
	
	function resetOrder ()
	{
		$this->resetQuery('order_by');
	}
	
	function resetLimit ()
	{
		$this->resetQuery('limit');
	}
	
	protected function resetQuery ($field)
	{
		if (isset($this->info[$field]))
		{
			unset($this->info[$field]);
		}
	}
	
	// Returns first row of a query
	function result ($field = null)
	{
		// Lighten the load on the DB server
		$this->limit(1);
		
		$r = $this->results(false);
		if ($r === false)
		{
			return false;
		}
		elseif (!isset($r[0]))
		{
			return null;
		}
		else
		{
			if ($field !== null)
			{
				return Arr::get($r[0], $field, null);
			}
			return $r[0];
		}
	}
	
	// Returns results of a query containing multiple rows
	function results ($reset = true)
	{
		$q = $this->getQuery();
		if ($this->cache === false || $this->cacheResult == null)
		{
			$result = $this->db->query($q, $this->queryParams);
			if ($this->cache === true)
			{
				$this->cacheResult = $result;
			}
		}
		elseif ($this->cacheResult !== null)
		{
			$result = $this->cacheResult;
		}
		
		if ($reset === true)
		{
			$this->reset();
		}
		if (count($result) < 1)
		{
			return false;
		}
		
		return $result;
	}
	
	// Returns execution result of a non-SELECT query
	function execute ($reset = true)
	{
		$q = $this->getQuery();
		$result = $this->db->execute($q, $this->queryParams);
		if ($reset === true)
		{
			$this->reset();
		}		
		return $result;
	}
}
