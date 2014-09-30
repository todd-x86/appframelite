<?php

/**
 * Auth
 *
 * Provides simplified access to an authentication layer for restricting user
 * access based on logins via a session.
 */

namespace Base;

class Auth
{
	// Session manager
	protected $session;
	
	// Constructor - requires an authentication group
	function __construct ($group)
	{
		$this->session = new Session('Auth_'.$group);
	}
	
	// Determines if authentication management is enabled
	function enabled ()
	{
		return $this->session->get('__active', false) === true;
	}
	
	// Retrieves a key from the authentication layer session
	function get ($key)
	{
		return $this->session->get($key, null);
	}
	
	// Associates a key with a value in the authentication layer session
	function set ($key, $value)
	{
		$this->session->set($key, $value);
	}
	
	// Disables the authentication manager
	function disable ()
	{
		$this->session->delete('__active');
	}
	
	// Enables the authentication manager
	function enable ()
	{
		$this->session->set('__active', true);
	}
}