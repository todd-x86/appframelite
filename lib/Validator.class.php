<?php

/**
 * Validator
 *
 * Validates a set of data by analyzing values in an associative array and
 * subjecting them to various validators and returning a Validator which
 * returns whether validation was successful and what errors exist.
 */

namespace Base;

class Validator
{
	// Error message strings
	const MSG_REQUIRED = '%s is a required field';
	const MSG_RANGE1 = '%s must be a value between %d and %d';
	const MSG_RANGE2 = '%s cannot be lower than %d';
	const MSG_RANGE3 = '%s cannot be higher than %d';
	const MSG_NUMERIC = '%s is a numeric field';
	const MSG_REGEX = '%s did not match the required pattern';
	const MSG_CALLBACK = '%s did not pass validation';
	const MSG_CURRENCY = '%s must be entered as currency';
	const MSG_EMAIL = '%s contains an invalid e-mail address';
	const MSG_TELEPHONE = '%s is not a valid phone number';
	const MSG_LENGTH1 = '%s must be at least %d characters long';
	const MSG_LENGTH2 = '%s must be at most %d characters long';
	const MSG_LENGTH3 = '%s must be between %d and %d characters long';
	const MSG_DECIMAL = '%s must be a decimal';
	const MSG_EQUALS = '%s must be "%s"';
	const MSG_SAME = '"%s" and "%s" are not the same';
	
	// Dataset used in validation
	protected $data;
	// Validation message list
	protected $messages;
	// Dataset key label mapping
	protected $labels;
	
	
	// Constructor
	private function __construct ($data)
	{
		$this->data = $data;
		$this->messages = array();
		$this->labels = null;
	}
	
	// Sets the key label mapping via an associative array
	function labels ($data)
	{
		$this->labels = $data;
		
		return $this;
	}
	
	// Returns a label for a key (if it exists),
	// otherwise a human-readable version of the ID
	protected function label ($key)
	{
		if (isset($this->labels[$key]))
		{
			return $this->labels[$key];
		}
		else
		{
			return Filter::readable($key);
		}
	}
	
	// Returns an error from the message list (first is default)
	function error ($index = 0)
	{
		return $this->messages[$index];
	}
	
	// Returns the list of error messages
	function errors ()
	{
		return $this->messages;
	}
	
	// Returns true if validation passed without errors
	function success ()
	{
		return is_array($this->messages) && count($this->messages) == 0;
	}
	
	// Creates a new Validator for a set of data
	public static function evaluate ($data)
	{
		return new Validator($data);
	}
	
	// Adds a custom validation error ($message), the generic message ($generic),
	// and any arguments needed for the message ($params)
	protected function addMessage ($message, $generic, $params)
	{
		if ($message === null)
		{
			array_push($this->messages, vsprintf($generic, $params));
		}
		else
		{
			array_push($this->messages, $message);
		}
	}
	
	// [Validation rule]
	// Ensures that a key is required to exist and not be empty
	function required ($key, $msg = null)
	{
		if (is_array($key))
		{
			if (is_array($msg))
			{
				foreach ($key as $field)
				{
					$this->required($field, array_shift($msg));
				}
			}
			else
			{
				foreach ($key as $field)
				{
					$this->required($field, $msg);
				}
			}
		}
		else
		{
			if (!isset($this->data[$key]) || strlen(trim($this->data[$key])) < 1)
			{
				$this->addMessage($msg, self::MSG_REQUIRED, array($this->label($key)));
			}
		}
		
		return $this;
	}
	
	// Checks if a string is a valid phone number by counting the digits
	protected function valid_phone ($str, $digits)
	{
		$str = preg_replace('/[\s\-\.\(\)]/', '', $str);
		return ctype_digit($str) && strlen($str) >= $digits;
	}
	
	// [Validation rule]
	// Determines if a key is a valid phone number
	function phone ($key, $digits = 10, $msg = null)
	{
		if ($this->hasInput($key) && !$this->valid_phone($this->data[$key], $digits))
		{
			$this->addMessage($msg, self::MSG_TELEPHONE, array($this->label($key)));
		}
		return $this;
	}
	
	// [Validation rule]
	// Ensures that a key must fall within a certain range ($low - $high)
	function range ($key, $low, $high = false, $msg = null)
	{
		if ($low !== false && $high !== false)
		{
			if ($this->hasInput($key) && ($this->data[$key] < $low || $this->data[$key] > $high))
			{
				$this->addMessage($msg, self::MSG_RANGE1, array($this->label($key), $low, $high));
			}
		}
		elseif ($low !== false)
		{
			if ($this->hasInput($key) && $this->data[$key] < $low)
			{
				$this->addMessage($msg, self::MSG_RANGE2, array($this->label($key), $low));
			}
		}
		else
		{
			if ($this->hasInput($key) && $this->data[$key] > $high)
			{
				$this->addMessage($msg, self::MSG_RANGE3, array($this->label($key), $high));
			}
		}
		
		return $this;
	}
	
	// [Validation rule]
	// Determines if a key is a floating point decimal number
	function decimal ($key, $msg = null)
	{
		if ($this->hasInput($key) && !preg_match('/^([0-9]+\.[0-9]+|[0-9]+)$/', $this->data[$key]))
		{
			$this->addMessage($msg, self::MSG_DECIMAL, array($this->label($key)));
		}
		return $this;
	}
	
	// [Validation rule]
	// Determines if a key is entirely numeric (integers only)
	function numeric ($key, $msg = null)
	{
		if (is_array($key))
		{
			if (is_array($msg))
			{
				foreach ($key as $field)
				{
					$this->numeric($field, array_shift($msg));
				}
			}
			else
			{
				foreach ($key as $field)
				{
					$this->numeric($field, $msg);
				}
			}
		}
		else
		{
			if ($this->hasInput($key) && !ctype_digit($this->data[$key]) && ($this->data[$key][0] == '-' && !ctype_digit(substr($this->data[$key], 1))))
			{
				$this->addMessage($msg, self::MSG_NUMERIC, array($this->label($key)));
			}
		}
		
		return $this;
	}
	
	// [Validation rule]
	// Validates a key based on a regular expression pattern
	function regex ($key, $pattern, $msg = null)
	{
		if ($this->hasInput($key) && !preg_match($pattern, $this->data[$key]))
		{
			$this->addMessage($msg, self::MSG_REGEX, array($this->label($key)));
		}
		
		return $this;
	}
	
	// [Validation rule]
	// Checks a callback by passing it the key's value and invalidates if the callback returns false
	function callback ($key, $callback, $msg = null)
	{
		if ($this->hasInput($key) && !call_user_func($callback, $this->data[$key]))
		{
			$this->addMessage($msg, self::MSG_CALLBACK, array($this->label($key)));
		}
		
		return $this;
	}
	
	// [Validation rule]
	// Checks a key conforms to an email address
	function email ($key, $msg = null)
	{
		if ($this->hasInput($key) && filter_var($this->data[$key], FILTER_VALIDATE_EMAIL) === false)
		{
			$this->addMessage($msg, self::MSG_EMAIL, array($this->label($key)));
		}
		
		return $this;
	}
	
	// [Validation rule]
	// Validates a number as being a currency (floating point with exactly 2 digits after)
	function currency ($key, $msg = null)
	{
		if ($this->hasInput($key) && !preg_match('/^[0-9]+($|\.[0-9]{2}$)/', $this->data[$key]))
		{
			$this->addMessage($msg, self::MSG_CURRENCY, array($this->label($key)));
		}
		
		return $this;
	}
	
	// Determines if a key has data from the input dataset
	function hasInput ($key)
	{
		return isset($this->data[$key]) && strlen($this->data[$key]) > 0;
	}
	
	// [Validation rule]
	// Validates two keys to ensure they contain the same data
	function same ($key1, $key2, $msg = null)
	{
		if ($this->hasInput($key1) || $this->hasInput($key2))
		{
			if (strcmp($this->data[$key1], $this->data[$key2]) !== 0)
			{
				$this->addMessage($msg, self::MSG_SAME, array($this->label($key1), $this->label($key2)));
			}
		}
		
		return $this;
	}
	
	// [Validation rule]
	// Validates a key's length is either at least a certain number of characters
	// or between a range of numbers ($low - $range).
	function length ($key, $low, $high = false, $msg = null)
	{
		if ($high === false)
		{
			if ($this->hasInput($key) && strlen($this->data[$key]) < $low)
			{
				$this->addMessage($msg, self::MSG_LENGTH1, array($this->label($key), $low));
			}
		}
		elseif ($low === false)
		{
			if ($this->hasInput($key) && strlen($this->data[$key]) > $high)
			{
				$this->addMessage($msg, self::MSG_LENGTH2, array($this->label($key), $high));
			}
		}
		else
		{
			if ($this->hasInput($key) && (strlen($this->data[$key]) > $high || strlen($this->data[$key]) < $low))
			{
				$this->addMessage($msg, self::MSG_LENGTH3, array($this->label($key), $low, $high));
			}
		}
		
		return $this;
	}
	
	// [Validation rule]
	// Validates a key matches a specific value
	function equals ($key, $value, $msg = null)
	{
		if ($this->hasInput($key) && strcmp($this->data[$key], $value) != 0)
		{
			$this->addMessage($msg, self::MSG_EQUALS, array($this->label($key), $value));
		}
		
		return $this;
	}
	
	// [Validation rule]
	// Validates some condition that must assert to true
	function assert ($case, $msg)
	{
		if ($case !== true)
		{
			$this->addMessage($msg, 'Assertion failed in Validator', array());
		}
		return $this;
	}
}
