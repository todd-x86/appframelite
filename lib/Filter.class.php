<?php

/**
 * Filter
 *
 * Helper class for applying filters and data transformations.
 */
 
namespace Base;

class Filter
{
	// Returns sanitized text safe for HTML output
	public static function html ($text)
	{
		return htmlentities($text);
	}
	
	// Generates a human-readable version of a variable name
	public static function readable ($text)
	{
		 return ucwords(preg_replace('/([a-z]+?)([A-Z]|([0-9]+))/', '$1 $2', str_replace('_', ' ', $text)));
	}
	
	// Returns human readable text for a file size (integer/long)
	public static function fileSize ($size)
	{
		$units = ['TB', 'GB', 'MB', 'KB', 'B'];
		$unit = array_pop($units);
		while ($size >= 1024 && count($units) > 0)
		{
			$size /= 1024;
			$unit = array_pop($units);
		}
		return round($size, 2).' '.$unit;
	}
}