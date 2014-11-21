<?php

/**
 * Expr
 *
 * Represents a SQL expression passed as an argument to a DynamicQuery object.
 */

namespace DynamicTable;

class Expr
{
	protected $expression;
	protected $params;
	
	function __construct ($expr_str, $params = null)
	{
		$this->expression = $expr_str;
		if ($params === null)
		{
			$this->params = null;
		}
		elseif (!is_array($params))
		{
			$this->params = [$params];
		}
		else
		{
			$this->params = $params;
		}
	}
	
	function getParams ()
	{
		return $this->params;
	}
	
	function toSql ()
	{
		return $this->expression;
	}
	
	function __toString ()
	{
		return $this->toSql();
	}
}
