<?php

/**
 * PHTML
 *
 * PHP/HTML renderer for the View class
 */

namespace Base\Render;
use Base\Render;

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
}