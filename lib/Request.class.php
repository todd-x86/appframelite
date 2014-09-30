<?php

/**
 * Request
 *
 * Encapsulates all request information such as $_GET, $_POST, and all headers
 * sent to the application from the browser.
 */

namespace Base;

class Request
{
	// Requested URI
	protected $uri;
	// HTTP POST data
	protected $post;
	// HTTP GET data (query string parameters)
	protected $get;
	// Request headers
	protected $headers;
	// Routing parameters
	protected $params;
	
	// Constructor (requires a URI)
	function __construct ($uri)
	{
		$this->uri = $uri;
		$this->get = $_GET;
		$this->post = $_POST;
		$this->headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
		$this->params = array();
	}
	
	// Returns true if request is sent over SSL
	function isHTTPS ()
	{
		return !empty($_SERVER['HTTPS']);
	}
	
	// Returns the calling script's filename
	function scriptFile ()
	{
		return $_SERVER['SCRIPT_FILENAME'];
	}
	
	// Returns the host name (domain name)
	function getHostName ()
	{
		return $_SERVER['HTTP_HOST'];
	}
	
	// Returns a value from HTTP GET
	function get ($key, $default = null)
	{
		return isset($this->get[$key]) ? $this->get[$key] : $default;
	}
	
	// Returns a value from HTTP POST
	function post ($key, $default = null)
	{
		return isset($this->post[$key]) ? $this->post[$key] : $default;
	}
	
	// Returns a file header for use in an upload via the File class
	function file ($id, $default = null)
	{
		return isset($_FILES[$id]) ? $this->generateFileArray($_FILES[$id]) : $default;
	}
	
	// Generates a file array used in the file upload process
	protected function generateFileArray ($file)
	{
		return ['filename' => $file['name'],
				'size' => $file['size'],
				'error' => isset($file['error']) ? $file['error'] : false,
				'tmp' => $file['tmp_name']];
	}
	
	// Returns an array of HTTP POST values
	function postArray ()
	{
		return $this->post;
	}
	
	// Returns true if request is an HTTP POST
	function isPost ()
	{
		return strcmp($_SERVER['REQUEST_METHOD'], 'POST') === 0;
	}
	
	// Returns the URI of the request
	function URL ()
	{
		return $this->uri;
	}
	
	// Returns the value for a router parameter
	function param ($key, $default = null)
	{
		return isset($this->params[$key]) && $this->params[$key] != '' ? $this->params[$key] : $default;
	}
	
	// Returns true if a value is in the HTTP POST set
	function posted ($key)
	{
		return isset($this->post[$key]);
	}
	
	// Returns the header for a specific key
	function header ($key)
	{
		return $this->headers[$key];
	}
	
	// Sets the routing parameters
	function params ($data)
	{
		$this->params = $data;
	}
}