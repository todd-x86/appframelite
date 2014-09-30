<?php

/**
 * Form
 *
 * Provides an encapsulation for data as well as a simplified way of generating
 * form fields (with Request-aware properties for reading submitted values upon
 * display).
 */

namespace Base;
use Base\UI\HTML;

class Form
{
	// Form submission method
	protected $method = 'post';
	// Shared request object
	protected $request;
	// Flag for responsive widgets upon submission
	protected $responsive = true;
	// Initial form data
	protected $data = null;
	
	
	// Constructor
	function __construct ($request)
	{
		$this->request = $request;
	}
	
	// Assigns a dataset to populate the form initially upon load
	function assign ($data)
	{
		$this->data = $data;
	}
	
	// Sets the form widgets to be responsive
	function setResponsive ($r)
	{
		$this->responsive = $r === true;
	}
	
	// Returns true if form widgets are set to be responsive
	function isResponsive ()
	{
		return $this->responsive;
	}
	
	// Returns an open form tag
	function open ()
	{
		return HTML::open('form', ['method' => $this->method]);
	}
	
	// Returns a closing form tag
	function close ()
	{
		return HTML::close('form');
	}
	
	// Returns a text input widget
	function text ($name, $attr = null)
	{
		return $this->input('text', $name, $attr);
	}
	
	// Returns an input widget
	function input ($type, $name, $attr = null)
	{
		if ($attr === null)
		{
			$attr = [];
		}
		$attr['type'] = $type;
		$attr['name'] = $name;
		$attr['id'] = $name;
		if ($this->responsive && $this->request->isPost())
		{
			$attr['value'] = $this->request->post($name);
		}
		elseif ($this->data !== null && isset($this->data[$name]))
		{
			$attr['value'] = $this->data[$name];
		}
		return HTML::tag('input', $attr);
	}
	
	// Returns a listbox widget
	function listbox ($name, $items, $selected = null, $attr = null)
	{
		if (!isset($attr['rows']))
		{
			$attr['rows'] = 5;
		}
		return $this->combo($name, $items, $selected, $attr);
	}
	
	// Returns a radio button widget
	function radio ($name, $value, $attr = null)
	{
		$attr += ['type' => 'radio'];
		return $this->checkedItem($name, $value, $attr);
	}
	
	// Returns a checkbox widget
	function checkbox ($name, $attr = null)
	{
		$attr += ['type' => 'checkbox'];
		return $this->hiddenCheckbox($name, '0').$this->checkedItem($name, '1', $attr);
	}
	
	// Returns a hidden checkbox input to overcome empty checkbox submissions
	protected function hiddenCheckbox ($name, $value)
	{
		return HTML::tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
	}
	
	// Returns a checked item for either a checkbox or radio button
	protected function checkedItem ($name, $value, $attr = null)
	{
		$attr += ['name' => $name, 'id' => $name, 'value' => $value];
		if ($this->responsive && $this->request->isPost() && $this->request->post($name) !== null)
		{
			$val = $this->request->post($name);
			if (is_array($val) && in_array($value, $val))
			{
				$attr['checked'] = 'checked';
			}
			elseif (strcmp($val, $value) === 0)
			{
				$attr['checked'] = 'checked';
			}
			elseif (isset($attr['checked']))
			{
				unset($attr['checked']);
			}
		}
		return HTML::tag('input', $attr);
	}
	
	// Returns a combo box widget
	function combo ($name, $items, $selected = null, $attr = null)
	{
		$attr += ['name' => $name, 'id' => $name];
		$result = HTML::open('select', $attr);
		
		if ($this->responsive && $this->request->isPost())
		{
			$selected = $this->request->post($name);
		}
		
		if (is_array($items) && count($items) > 0)
		{
			foreach ($items as $value => $contents)
			{
				$optAttr = ['value' => $value];
				if ($selected !== null)
				{
					if (is_string($selected) && strcmp($selected, $value) === 0)
					{
						$optAttr['selected'] = 'selected';
					}
					elseif (is_array($selected) && in_array($value, $selected))
					{
						$optAttr['selected'] = 'selected';
					}
				}
				$result .= HTML::tag('option', $optAttr, Filter::html($contents));
			}
		}
		
		$result .= HTML::close('select');
		return $result;
	}
	
	// Returns a password widget
	function password ($name, $attr = null)
	{
		return $this->input('password', $name, $attr);
	}
	
	// Returns a text area widget
	function textarea ($name, $content = null, $attr = null)
	{
		if ($attr === null)
		{
			$attr = [];
		}
		$attr['name'] = $name;
		$attr['id'] = $name;
		if ($this->responsive && $this->request->isPost())
		{
			$content = $this->request->post($name);
		}
		return HTML::tag('textarea', $attr, Filter::html($content));
	}
	
	// Returns a submission button widget
	function submit ($name, $attr = null)
	{
		return HTML::submit($name, $attr);
	}
}