<?php

/**
 * Result
 *
 * Contains results for a row inside of an object.
 */

namespace Base\Db;

class Result
{
	// Row data
	protected $data;
	
	
	// Constructor
	function __construct ($data)
	{
		return $this->data = $data;
	}
	
	// Returns a value for a key in the result set
	function __get ($key)
	{
		return $this->data[$key];
	}
	
	// Returns an array of the result set
	function toArray ()
	{
		return $this->data;
	}
}