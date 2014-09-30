<?php

/**
 * Model
 *
 * Provides foundation for using a basic model in AppFrame Lite without any
 * prerequisites such as DB access.
 */

namespace Base\MVC;
use Base\App;

abstract class Model
{
	protected $errors;
	
	// Constructor
	function __construct ()
	{
		$this->errors = [];
		$this->init();
	}
	
	// Init overload (for inheritance purposes)
	function init ()
	{
	
	}
	
	// Returns a model loaded in the application
	function __get ($modelName)
	{
		return App::Model($modelName);
	}
	
	// Reports an error to the error list
	function report ($text)
	{
		array_push($this->errors, $text);
	}
	
	// Resets the error list
	function clearErrors ()
	{
		$this->errors = [];
	}
	
	// Returns an error from the error list
	function error ($index = 0)
	{
		return $this->errors[$index];
	}
	
	// Returns all errors reported by the model
	function errors ()
	{
		return $this->errors;
	}
}