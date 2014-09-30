<?php

/**
 * Package
 *
 * Base class for configuring a customized application.
 */

namespace Base;

class Package
{
	// Returns an instance of the router used by the application
	function getRouter ()
	{
		return new Router();
	}
}