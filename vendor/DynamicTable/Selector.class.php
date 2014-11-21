<?php

namespace DynamicTable;

class Selector
{
	protected $current;
	protected $select;
	
	// Constructor
	function __construct ()
	{
		$this->current = array();
		$this->select = array();
	}
	
	// Builds select field with functions applied (if any)
	protected function buildFunction ($data)
	{
		if (count($this->current) < 1)
		{
			return $data;
		}
		else
		{
			$suff = str_repeat(')', count($this->current));
			$pref = implode('(', $this->current).'(';
			return $pref.$data.$suff;
		}
	}
	
	// Special overloads
	function concat ()
	{
		array_push($this->select, 'concat('.implode(',', func_get_args()).')');
		return $this;
	}
	
	// SQL function calls
	// NOTE: Instead of implementing all functions and since most take one parameter,
	//       I decided to use a __call overload magic function for building the
	//       function calls -- when arguments are given then the field is added
	//       with surrounding function calls
	function __call ($name, $args)
	{
		// Raw simply means we want it untouched or without functions
		if ($name !== 'raw')
		{
			array_push($this->current, $name);
		}
		
		// Passing params indicates the function calls have ended
		if (count($args) > 0)
		{
			// Record all fields to be called by the functions
			foreach ($args as $field)
			{
				array_push($this->select, $this->buildFunction($field));
			}
			$this->current = array();
		}
		
		return $this;
	}
	
	// Return all fields requested in Selector
	function __fields ()
	{
		return array_map(function ($item) {
			return explode(' ', $item)[0];
		}, $this->select);
	}
	
	// To avoid the off-chance of mistakenly calling this, I made it a magic toString function
	function __toString ()
	{
		return implode(',', $this->select);
	}
}
