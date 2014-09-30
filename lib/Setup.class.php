<?php

/**
 * Setup
 *
 * Provides functionality and control for installing and configuring the
 * application on a Web server.
 */

namespace Base;

use Base\UI\HTML;
use Base\IO\File;
use Base\IO\Dir;

class Setup
{
	// Current HTTP Request object
	protected $request = null;
	// Validator
	public $validate = null;
	// Content storage
	protected $content = [];
	// Success message
	protected $success = null;
	// Database settings
	protected $dbConfig = [];
	// Database connections
	protected $db = [];
	// Output content
	protected $output = '';
	// Form handler
	public $form = null;
	
	// Event handler storage
	protected $before = null;
	protected $beforeSubmit = null;
	protected $submit = null;
	protected $afterSubmit = null;
	protected $validateEvent = null;
	protected $init = null;
	
	
	// Constructor
	function __construct ()
	{
		// Prepare Request
		$this->request = new Request('/');
		$this->form = new Form($this->request);
	}
	
	/* Event handlers */
	
	// Event: before the setup is rendered
	function before ($callback)
	{
		$this->before = $callback;
	}
	
	// Event: after the setup is finished
	function afterSubmit ($callback)
	{
		$this->afterSubmit = $callback;
	}
	
	// Event: during data validation of a submission
	function validate ($callback)
	{
		$this->validateEvent = $callback;
	}
	
	// Event: before validation during a submission
	function beforeSubmit ($callback)
	{
		$this->beforeSubmit = $callback;
	}
	
	// Event: submission event
	function submit ($callback)
	{
		$this->submit = $callback;
	}
	
	// Event: setup initialization
	function init ($callback)
	{
		$this->init = $callback;
	}
	
	
	/* Main methods */
	
	// Connects to a database
	function dbConnect ($id)
	{
		$conf = $this->dbConfig[$id];
		$this->db[$id] = new Db($conf['server'], $conf['user'], $conf['pass'], $conf['db'], $conf['type']);
		return $this->db[$id]->connected();
	}
	
	// Sets the DB connection settings for a database ID
	function dbSettings ($id, $settings)
	{
		if (!isset($settings['type']))
		{
			$settings['type'] = 'mysql';
		}
		$this->dbConfig[$id] = $settings;
	}
	
	// Creates a database from a content block of SQL
	function dbCreate ($id, $contentId)
	{
		return $this->db[$id]->execute($this->content[$contentId]);
	}
	
	// Creates a new UI control group
	function beginGroup ($text)
	{
		$this->output .= HTML::open('fieldset').HTML::tag('legend', null, $text);
	}
	
	// Closes the UI control group
	function endGroup ()
	{
		$this->output .= HTML::close('fieldset');
	}
	
	// Adds a new text field to the output content
	function textField ($label, $id, $default = null)
	{
		$this->row($label, $this->form->text($id, ['value' => $default]));
	}
	
	// Adds a new password field to the output content
	function passwordField ($label, $id, $default = null)
	{
		$this->row($label, $this->form->password($id, ['value' => $default]));
	}
	
	// Adds a new label and field to the output content
	protected function row ($label, $field)
	{
		$this->output .= HTML::open('div', ['class' => 'row']);
		
		// Label
		$this->output .= HTML::tag('label', null, $label);
		
		// Field
		$this->output .= HTML::open('div', ['class' => 'field']);
		$this->output .= $field;
		$this->output .= HTML::close('div');
		
		$this->output .= HTML::close('div');
	}
	
	// Returns the Request object generated from the built-in controller
	function getRequest ()
	{
		return $this->request;
	}
	
	// Sets a generic success message displayed when the setup is finished
	function success ($msg)
	{
		$this->success = $msg;
	}
	
	// Causes the Setup to cease execution and display an error
	function error ($msg)
	{
		throw new Exception('Setup Error', $msg);
	}
	
	// Returns the array of HTTP POST values from the request
	function post ()
	{
		return $this->request->postArray();
	}
	
	// Returns a value from the HTTP POST array
	function postValue ($id)
	{
		return $this->request->post($id);
	}
	
	// Sets a block of content for DB / file purposes
	function contentSet ($id, $contents)
	{
		$this->content[$id] = $contents;
	}
	
	// Creates a new file and writes a content block to it
	function fileCreate ($file, $contentId)
	{
		$fp = new File($file);
		if (!isset($this->content[$contentId]))
		{
			throw new Exception('Content Error', sprintf('The requested content block "%s" was never set', $contentId));
		}
		if (!$fp->contents($this->content[$contentId]))
		{
			throw new Exception('File Create Error', sprintf('Could not create file "%s"', $file));
		}
		
		return true;
	}
	
	// Creates a directory
	function dirCreate ($dir, $perms)
	{
		$d = new Dir($dir);
		if (!$d->exists() && !$d->create($perms))
		{
			throw new Exception('Directory Create Error', sprintf('Could not create directory "%s"', $dir));
		}
		
		return true;
	}
	
	// Returns true if a directory path is writable
	function dirIsWritable ($dir)
	{
		$d = new Dir($dir);
		return $d->isWritable();
	}
	
	// Removes calling PHP file
	function dissolve ()
	{
		$f = new File($this->request->scriptFile());
		if (!$f->delete())
		{
			throw new Exception('Dissolve Error', sprintf('Setup completed but could not remove the installation file - please remove "%s" manually', $f->basename()));
		}
	}
	
	// Executes the Setup controller
	function run ()
	{
		$page = Template::fromFile('app/protected/install.tpl');
		try
		{
			$this->executeSetup();
		}
		catch (Exception $e)
		{
			$page->error = $e->getMessage();
		}
		
		if ($this->success !== null)
		{
			$page->success = $this->success;
		}
		
		$page->content = $this->output;
		
		// Render template
		$page->render();
	}
	
	// Invokes a callback if it's not null
	protected function invoke ($callback, $params)
	{
		if ($callback !== null)
		{
			call_user_func_array($callback, $params);
		}
	}
	
	// Executes the body of code run by the setup program
	protected function executeSetup ()
	{
		// Init
		$this->invoke($this->init, [$this]);
		
		// Before
		$this->invoke($this->before, [$this]);
		
		if ($this->request->isPost())
		{
			$this->invoke($this->beforeSubmit, [$this]);
			
			// Validate & submit
			$this->validate = Validator::evaluate($this->post());
			$this->invoke($this->validateEvent, [$this, $this->post()]);
			if (!$this->validate->success())
			{
				throw new Exception('Validation Error', $this->validate->error(0));
			}	
			$this->invoke($this->submit, [$this]);
			
			$this->invoke($this->afterSubmit, [$this]);
		}
	}
}