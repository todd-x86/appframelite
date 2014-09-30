<?php

/**
 * Autoloader
 *
 * Establishes the standard autoloader for AppFrame Lite object instantiation -
 * [ Primary ] \ [ Directory ] \ [ Class Name ]
 */

spl_autoload_register(function ($class) {
	if (substr($class, 0, 1) === '\\')
	{
		$class = substr($class, 1);
	}
	
	$segments = explode('\\', $class);
	
	$className = array_pop($segments);
	$firstSeg = array_shift($segments);
	
	switch ($firstSeg)
	{
		case 'App':
			$primaryDir = 'app';
			break;
		case 'Base':
			$primaryDir = 'lib';
			break;
		default:
			$primaryDir = 'vendor/'.$firstSeg;
	}
	
	$file = $primaryDir.'/'.implode('/', $segments).'/'.$className.'.class.php';
	if (file_exists($file))
	{
		require($file);
		return true;
	}
	else
	{
		return false;
	}
});
