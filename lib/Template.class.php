<?php

/**
 * Template
 *
 * Simple template engine that compiles to PHP and eval's the result.
 */

namespace Base;
use Base\IO\File;
use Base\MVC\View;

class Template
{
	// PHP tag constants
	const PHP_START = '<?php ';
	const PHP_END = ' ?>';
	
	// Template source code
	protected $code;
	// Output PHP code
	protected $output;
	// Template path
	protected $path = null;
	// Main template file
	protected $defaultFile = null;
	// Template data
	protected $data = array();
	// Template function callbacks
	protected $callbacks = array();
	
	
	// Sets a key-value pair in the template data
	function __set ($k, $value)
	{
		$this->data[$k] = $value;
	}

	// Returns a value from a key-value pair in the template data
	function __get ($var)
	{
		return $this->data[$var];
	}

	// Returns true if a key is set in the template data
	function __isset ($var)
	{
		return isset($this->data[$var]);
	}
	
	// Invokes a template function and returns the result, null if the function doesn't exist
	function __call ($name, $args)
	{
		if (isset($this->callbacks[$name]))
		{
			return call_user_func_array($this->callbacks[$name], $args);
		}
		else
		{
			return null;
		}
	}

	// Constructor (requires source code)
	protected function __construct ($code)
	{
		$this->code = explode("\n", $code);
		$this->output = '';
		$this->addFunction('theme', array($this, 'theme'));
		$this->addFunction('url', array($this, 'url'));
		$this->addFunction('filter', array($this, 'filter'));
		$this->addFunction('include', array($this, 'tInclude'));
		$this->addFunction('content', array($this, 'content'));
		$this->addFunction('date', array($this, 'date'));
		$this->addFunction('filesize', array($this, 'filesize'));
		$this->addFunction('hasFlash', array($this, 'hasFlash'));
		$this->addFunction('flash', array($this, 'getFlash'));
	}

	// Builds the template to output
	protected function build ()
	{
		if (count($this->code) > 0)
		{
			foreach ($this->code as $line)
			{
				$this->output .= $this->compileLine($line)."\n";
			}
		}
	}

	// Returns the template output
	protected function getOutput ()
	{
		return $this->output;
	}

	// Compiles a line from the template source
	protected function compileLine ($line)
	{
		// {{ @statement: }}
		$line = preg_replace('/\{\{\s*@(.+?):\s*\}\}/', self::PHP_START.'print \$this->filter(\$this->$1());'.self::PHP_END, $line);

		// {{ @statement(...) }}
		$line = preg_replace('/\{\{\s*@(\w+?)\((.+?)\)\s*\}\}/', self::PHP_START.'print \$this->filter(\$this->$1($2));'.self::PHP_END, $line);
		
		// {{ @statement }}
		$line = preg_replace('/\{\{\s*@(.+?)\s*\}\}/', self::PHP_START.'print \$this->filter(isset(\$$1) ? \$$1 : \'\');'.self::PHP_END, $line);

		// {{ statement: }}
		$line = preg_replace('/\{\{\s*(.+?)\:\s*\}\}/', self::PHP_START.'print \$this->$1();'.self::PHP_END, $line);

		// {{ statement(...) }}
		$line = preg_replace('/\{\{\s*(.+?)\((.+?)\)\s*\}\}/', self::PHP_START.'print \$this->$1($2);'.self::PHP_END, $line);
		
		// {{ statement }}
		$line = preg_replace('/\{\{\s*(.+?)\s*\}\}/', self::PHP_START.'print \$$1;'.self::PHP_END, $line);

		// { foreach $x as $y } or { foreach $x -> $y }
		$line = preg_replace('/\{\s*foreach\s+(.+?)\s+(as|\-\>)\s+(.+?)\s*\}/i', self::PHP_START.'if (isset($1) && is_array($1) && count($1) > 0) foreach ($1 as $3) {'.self::PHP_END, $line);

		// { end }
		$line = preg_replace('/\{\s*end\s*\}/i', self::PHP_START.'}'.self::PHP_END, $line);
		
		// { display(var) }
		$line = preg_replace('/\{\s*display\s*\((.+?)\)\s*\}/i', self::PHP_START.'if (isset(\$$1)) {'.self::PHP_END, $line);
		
		// { if $x }
		$line = preg_replace('/\{\s*if\s+([A-Za-z0-9\_]+?)\s*\}/i', self::PHP_START.'if (isset($1)) {'.self::PHP_END, $line);

		// { if .. }
		$line = preg_replace('/\{\s*if\s+(.+?)\s*\}/i', self::PHP_START.'if ($1) {'.self::PHP_END, $line);
		
		// { else }
		$line = preg_replace('/\{\s*else\s*\}/i', self::PHP_START.'} else {'.self::PHP_END, $line);

		// { elseif .. }
		$line = preg_replace('/\{\s*elseif\s+(.+?)\s*\}/i', self::PHP_START.'} elseif ($1) {'.self::PHP_END, $line);
		
		// {! ... }
		$line = preg_replace('/\{\!\s*(.+?)\s*\}/i', self::PHP_START.'$1'.self::PHP_END, $line);

		return $line;
	}
	
	// [Template Function]
	// Returns a resolved URI
	protected function url ($text)
	{
		return Path::web($text);
	}
	
	// [Template Function]
	// Returns a formatted date from a UNIX timestamp
	protected function date ($fmt, $timestamp)
	{
		return date($fmt, $timestamp);
	}
	
	// [Template Function]
	// Returns a human-readable file size
	protected function filesize ($size)
	{
		return Filter::fileSize($size);
	}
	
	// [Template Function]
	// Returns true if a flash message was set
	protected function hasFlash ()
	{
		return View::hasFlash();
	}
	
	// [Template Function]
	// Returns the flash message
	protected function getFlash ()
	{
		return View::getFlash();
	}
	
	// [Template Function]
	// Returns a URI resolved to the current theme directory
	protected function theme ($text)
	{
		return Path::theme($text);
	}
	
	// [Template Function]
	// Includes a template file and executes it
	protected function tInclude ($file)
	{
		try
		{
			Template::fromFile($file)->render();
		}
		catch (Exception $e)
		{
			App::displayException($e);
		}
	}
	
	// [Template Function]
	// Renders content file or main content with calling object's template data
	protected function content ($file = null)
	{
		if ($file === null)
		{
			$file = $this->defaultFile;
		}
		
		// Add path
		if ($this->path !== null)
		{
			$file = $this->path.'/'.$file;
		}
		
		// Concatenate extension
		$file .= '.tpl';
		
		try
		{
			$tpl = Template::fromFile($file);
		
			$tpl->data = $this->data;
		
			$tpl->render();
		}
		catch (Exception $e)
		{
			App::displayException($e);
		}
	}
	
	// [Template Function]
	// Returns HTML-sanitized data
	protected function filter ($text)
	{
		return Filter::html($text);
	}

	// Compiles template source code and returns the Template object
	public static function compile ($code)
	{
		$p = new Template($code);
		$p->build();
		return $p;
	}
	
	// Sets the template path
	function setPath ($path)
	{
		$this->path = $path;
	}
	
	// Sets the main template file
	function setFile ($f)
	{
		$this->defaultFile = $f;
	}
	
	// Adds a template function matched with a callback
	function addFunction ($name, $callback)
	{
		$this->callbacks[$name] = $callback;
	}
	
	// Returns the PHP output
	function toPHP ()
	{
		return $this->output;
	}
	
	// Renders the template from compiled PHP
	function render ()
	{
		extract($this->data, EXTR_SKIP);
		eval('?>'.$this->toPHP());
	}
	
	// Renders the template from compiled PHP and returns the output
	function _render ()
	{
		ob_start();
		$this->render();
		$c = ob_get_contents();
		ob_end_clean();
		return $c;
	}
	
	// Returns a Template object from a parsed file
	public static function fromFile ($file)
	{
		if (!File::isFile($file))
		{
			throw new Exception('File Not Found', sprintf('Template file "%s" could not be loaded', (new File($file))->basename()));
		}
		$contents = (new File($file))->contents();
		return self::compile($contents);
	}
}
