<?php

/**
 * DbModel
 *
 * Extension of the Model class with DB access methods.
 */

namespace Base\MVC\Model;
use Base\App;
use Base\MVC\Model;

class DbModel extends Model
{
	// Database connection
	protected $db;
	// Table name
	protected $table;
	
	
	// Constructor
	function __construct ()
	{
		parent::__construct();
		$this->db = App::Database($this->db);
	}
}