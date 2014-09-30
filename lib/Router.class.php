<?php

/**
 * Router
 *
 * Loads pre-defined mapping of URIs to controllers and actions.  
 */

namespace Base;

class Router
{
	// Route URI tree (URI tree -> action)
	protected $routes = [];
	// Route URI tree reversed (action -> URI)
	protected $reversed = [];
	
	
	// Loads a route file into the URI tree (i.e. routes.conf)
	function load ($file)
	{
		$routes = parse_ini_file($file, true);
		foreach ($routes as $controller => $cRoutes)
		{
			foreach ($cRoutes as $method => $route)
			{
				$this->add($route, [$controller, $method]);
			}
		}
	}
	
	// Looks up a controller with named parameters to build a URI
	// NOTE: An exception is thrown if the URL was not found
	function reverseLookup ($controller, $params = null)
	{
		if (!isset($this->reversed[$controller]))
		{
			throw new Exception('URL Not Found', sprintf('"%s" could not be found', $controller));
		}
		
		$url = $this->reversed[$controller];
		
		if ($params !== null && is_array($params) && count($params) > 0)
		{
			foreach ($params as $key => $value)
			{
				$url = preg_replace('/\{(+|)'.$key.'\}/', $value, $url);
			}
		}
		
		return Path::web($url);
	}
	
	// Translates a URI ($route) into a controller and action
	// False is returned when no match is found
	function lookup ($route)
	{
		// Remove double slashes
		while (strpos($route, '//') !== false)
		{
			$route = str_replace('//', '/', $route);
		}
		
		// Remove beginning and ending slashes
		if (substr($route, 0, 1) === '/')
		{
			$route = substr($route, 1);
		}
		if (substr($route, -1) === '/')
		{
			$route = substr($route, 0, -1);
		}
		
		$params = array();
		
		$segments = array_reverse(explode('/', $route));
		
		$tmp = &$this->routes;
		
		while (count($segments) > 0)
		{
			$seg = array_pop($segments);
			
			// Match
			if (isset($tmp[$seg]))
			{
				$tmp = &$tmp[$seg];
			}
			elseif (isset($tmp['@']))
			{
				$params[$tmp['@']['name']] = $seg;
				
				$tmp = &$tmp['@']['next'];
			}
			elseif (isset($tmp['*']))
			{
				$params[$tmp['*']['name']] = implode('/', array_merge([$seg], array_reverse($segments)));
				$tmp = &$tmp['*']['next'];
				$segments = null;
			}
			else
			{
				return false;
			}
		}
		
		// Check for ending wildcard param
		if (!isset($tmp[0]) && !isset($tmp[1]) && isset($tmp['*']))
		{
			$params[$tmp['*']['name']] = implode('/', array_reverse($segments));
			$tmp = &$tmp['*']['next'];
			$segments = null;
		}
		
		// Not a callable
		if (!isset($tmp[0]) && !isset($tmp[1]))
		{
			return false;
		}
		
		$callable = $tmp;
		
		return [$callable, $params];
	}
	
	// Adds a new route and controller-action callable to the URI route tree
	function add ($route, $callable)
	{
		// Remove beginning and ending slashes
		if (substr($route, 0, 1) === '/')
		{
			$route = substr($route, 1);
		}
		if (substr($route, -1) === '/')
		{
			$route = substr($route, 0, -1);
		}
		
		$this->reversed[$callable[0].'::'.$callable[1]] = $route;
		
		$segments = array_reverse(explode('/', $route));
		$tmp = &$this->routes;
		
		$seg = array_pop($segments);
		while (count($segments) > 0)
		{
			// Match special cases
			if (preg_match('/\{\+(.+?)\}/', $seg, $match))
			{
				// Named wildcard
				if (!isset($tmp['*']))
				{
					$tmp['*'] = ['next' => array(), 'name' => $match[1]];
				}
				$tmp = &$tmp['*']['next'];
			}
			elseif (preg_match('/\{(.+?)\}/', $seg, $match))
			{
				// Named parameter
				if (!isset($tmp['@']))
				{
					$tmp['@'] = ['next' => array(), 'name' => $match[1]];
				}
				$tmp = &$tmp['@']['next'];
			}
			else
			{
				if (!isset($tmp[$seg]))
				{
					$tmp[$seg] = array();
				}
				
				// Traverse
				$tmp = &$tmp[$seg];
			}
			
			$seg = array_pop($segments);
		}
		
		if (preg_match('/\{\+(.+?)\}/', $seg, $match))
		{
			$tmp['*'] = ['next' => $callable, 'name' => $match[1]];
		}
		elseif (preg_match('/\{(.+?)\}/', $seg, $match))
		{
			$tmp['@'] = ['next' => $callable, 'name' => $match[1]];
		}
		else
		{
			$tmp[$seg] = $callable;
		}
	}
}