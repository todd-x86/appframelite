<?php

/**
 * Render
 *
 * Adapter class for plugins that wish to utilize rendering functions in the
 * View class in MVC.
 */

namespace Base;

abstract class Render
{
	// View-specific data
	protected $data;
	// Layout file
	protected $layout;
	// View path
	protected $path;
	// View file
	protected $file;
	
	
	// Render function for generating output in the View
	abstract function render ();
	
	// Returns the file to render
	protected function getRenderFile ()
	{
		// Use layout file or template depending on content
		if ($this->layout !== null)
		{
			$file = $this->layout;
		}
		else
		{
			$file = $this->file;
		}
		
		// Add path
		if ($this->path !== null)
		{
			$file = $this->path.'/'.$file;
		}
		return $file;
	}
	
	// Sets view-specific data
	function setData ($data)
	{
		$this->data = $data;
	}
	
	// Sets the main layout template
	function setLayout ($file)
	{
		$this->layout = $file;
	}
	
	// Sets the content body file
	function setContent ($file)
	{
		$this->file = $file;
	}
	
	// Sets the path of the layout and content
	function setPath ($path)
	{
		$this->path = $path;
	}
}
