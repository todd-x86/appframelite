<?php

/**
 * HTML
 *
 * View helper for HTML-based generation.
 */

namespace Base\UI;
use Base\Filter;

class HTML
{
	// Returns an array as HTML attributes
	public static function attributes ($data)
	{
		$result = '';
		if (is_array($data) && count($data) > 0)
		{
			foreach ($data as $key => $value)
			{
				if ($value !== false)
				{
					$result .= sprintf(' %s="%s"', $key, Filter::html($value));
				}
			}
		}
		return $result;
	}
	
	// Returns a complete HTML tag
	public static function tag ($type, $attr = null, $content = false)
	{
		if ($content !== false)
		{
			return self::open($type, $attr).$content.self::close($type);
		}
		else
		{
			return sprintf('<%s%s />', $type, self::attributes($attr));
		}
	}
	
	// Returns a submit button
	public static function submit ($name, $attr = null)
	{
		return self::tag('input', $attr + ['type' => 'submit', 'name' => $name, 'id' => $name]);
	}
	
	// Returns an HTML open tag
	public static function open ($type, $attr = null)
	{
		return sprintf('<%s%s>', $type, self::attributes($attr));
	}
	
	// Returns an HTML close tag
	public static function close ($type)
	{
		return sprintf('</%s>', $type);
	}
}