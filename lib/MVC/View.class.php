<?php

/**
 * View
 *
 * Handles presentation of the AppFrame Lite application through direction by
 * a Controller and uses a ViewAdapter for processing View files.
 */

namespace Base\MVC;
use Base\App;

class View
{
	// Theme name
	protected static $theme = null;
	// View renderer / adapter
	public static $adapter = null;
	// View data
	protected $data = array();
	// View file path
	protected $path = null;
	
	
	// Sets the view path
	function setPath ($p)
	{
		$this->path = $p;
	}
	
	// Retrieves view data
	function __get ($key)
	{
		return $this->data[$key];
	}
	
	// Sets view data
	function __set ($key, $value)
	{
		$this->data[$key] = $value;
	}
	
	// Renders a view file
	function render ($tplFile)
	{
		// Check adapter
		if (self::$adapter === null)
		{
			self::setAdapter('PHTML');
		}
		
		if (self::$theme !== null)
		{
			$themePath = 'themes/'.self::$theme.'/';
			if ($this->path !== null)
			{
				self::$adapter->setPath($themePath.$this->path);
			}
			else
			{
				self::$adapter->setPath($themePath);
			}
			self::$adapter->setLayout('template');
		}
		elseif ($this->path !== null)
		{
			self::$adapter->setPath($this->path);
		}
		self::$adapter->setData($this->data);
		self::$adapter->setContent($tplFile);
		self::$adapter->render();
	}
	
	// Renders the view data as a JSON object
	function renderAsJSON ()
	{
		header('Content-Type: application/json');
		print json_encode($this->data, true);
	}
	
	// Sets the View adapter / renderer
	public static function setAdapter ($name)
	{
		$className = '\\Base\\Render\\'.$name;
		self::$adapter = new $className();
	}
	
	// Returns the View theme
	public static function getTheme ()
	{
		return self::$theme;
	}
	
	// Sets the View theme
	public static function setTheme ($theme)
	{
		self::$theme = $theme;
	}
	
	// Checks if a flash message was set
	public static function hasFlash ()
	{
		return App::Session('Flash')->exists('message');
	}
	
	// Returns the flash message (if one was set) and removes it
	public static function getFlash ()
	{
		$msg = App::Session('Flash')->get('message');
		App::Session('Flash')->delete('message');
		return $msg;
	}
	
	// Flashes a message in the user's session
	public static function flashMessage ($msg)
	{
		App::Session('Flash')->set('message', $msg);
	}
}