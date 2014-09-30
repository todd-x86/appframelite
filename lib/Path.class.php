<?php

/**
 * Path
 *
 * Contains application path-based and URI-based methods for resolving paths
 * dependent on the application's physical location either on the server or
 * in the browser.
 */

namespace Base;
use Base\MVC\View;
use Base\IO\File;

class Path
{
	// Base path (from init)
	protected static $dir;
	// Base URI (from init)
	protected static $uri;
	
	
	// Initializes the base path
	public static function initDir ($path)
	{
		if (substr($path, -1) !== '/')
		{
			$path .= '/';
		}
		self::$dir = $path;
	}
	
	// Initializes the base URI
	public static function initURI ()
	{
		$uri = $_SERVER['SCRIPT_NAME'];
		
		// If mod_rewrite is not enabled, pass everything through index.php
		if (!function_exists('apache_get_modules') || in_array('mod_rewrite', apache_get_modules()))
		{
			$uri = str_replace((new File($_SERVER['SCRIPT_FILENAME']))->basename(), '', $uri);
		}
		
		if (substr($uri, -1) !== '/')
		{
			$uri .= '/';
		}
		self::$uri = $uri;
	}
	
	// Resolves a URI for use in the application
	// NOTE: Passing a Request object will offer a full URL
	public static function web ($path, $request = null)
	{
		if (substr($path, 0, 1) === '/')
		{
			$path = substr($path, 1);
		}
		if ($request !== null)
		{
			$url = $request->isHTTPS() ? 'https' : 'http';
			$url .= '://';
			$url .= $request->getHostName();
			return $url.self::$uri.$path;
		}
		else
		{
			return self::$uri.$path;
		}
	}
	
	// Resolves a local path in the application's framework directory
	public static function local ($path)
	{
		if (substr($path, 0, 1) === '/')
		{
			$path = substr($path, 1);
		}
		return self::$dir.$path;
	}
	
	// Resolves a URI in the application's current theme path
	public static function theme ($path)
	{
		if (substr($path, 0, 1) === '/')
		{
			$path = substr($path, 1);
		}
		$themeDir = View::getTheme();
		if ($themeDir !== null)
		{
			$themeDir .= '/';
		}
		return self::web('themes/'.$themeDir.$path);
	}
}