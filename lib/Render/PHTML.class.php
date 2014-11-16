<?php

/**
 * PHTML
 *
 * PHP/HTML renderer for the View class
 */

namespace Base\Render;
use Base\Render;
use Base\Filter;
use Base\Path;
use Base\MVC\View;

class PHTML extends Render
{
	// Renders the PHP/HTML document
	function render ()
	{
		$file = $this->getRenderFile().'.phtml';
		$this->renderPHTML($file);
	}
	
	// Renders the actual PHP file
	protected function renderPHTML ($_file)
	{
		extract($this->data, EXTR_SKIP);
		require($_file);
	}
	
	function w ($text)
	{
		print Filter::html($text);
	}
	
	protected function content ()
	{
		$this->renderPHTML($this->path.'/'.$this->file.'.phtml');
	}
	
	protected function theme ($text)
	{
		return Path::theme($text);
	}
	
	protected function url ($text)
	{
		return Path::web($text);
	}
	
	protected function date ($fmt, $timestamp)
	{
		return date($fmt, $timestamp);
	}
	
	// [Template Function]
	// Returns a human-readable file size
	protected function filesize ($size)
	{
		return Filter::fileSize($size);
	}
	
	// [Template Function]
	// Returns true if a flash message was set
	protected function hasFlash ()
	{
		return View::hasFlash();
	}
	
	// [Template Function]
	// Returns the flash message
	protected function flash ()
	{
		return View::getFlash();
	}
}