<?php

/**
 * Exception
 *
 * Extension of the SPL Exception class with a title for use as a Web-based
 * error message.
 */

namespace Base;

class Exception extends \Exception
{
	// Exception title
	protected $title;
	
	// Constructor
	function __construct ($title, $message)
	{
		parent::__construct($message);
		$this->title = $title;
	}
	
	// Returns the title of the Exception
	function getTitle ()
	{
		return $this->title;
	}
}