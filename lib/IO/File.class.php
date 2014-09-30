<?php

/**
 * File
 *
 * Provides file access including file system operations and retrieving
 * information about specific files.
 */

namespace Base\IO;
use Base\Filter;
use \SplFileInfo;

class File
{
	// Filename
	protected $file;
	
	
	// Constructor
	function __construct ($file)
	{
		$this->file = $file;
	}
	
	// Creates the new file
	function create ($perms = 0644)
	{
		touch($this->file);
		return @chmod($this->file, $perms);
	}
	
	// Removes the file
	function delete ()
	{
		return @unlink($this->file);
	}
	
	// Copies the file to a destination
	function copy ($dest)
	{
		return @copy($this->file, $dest);
	}
	
	// Returns the file extension
	function extension ()
	{
		return (new SplFileInfo($this->file))->getExtension();
	}
	
	// Returns true if the file exists
	function exists ()
	{
		return file_exists($this->file);
	}
	
	// Returns the basename of the file
	function basename ()
	{
		return basename($this->file);
	}
	
	// Returns the MIME type for the file
	function mime ()
	{
		$f = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($f, $this->file);
		finfo_close($f);
		return $mime;
	}
	
	// Returns the human-readable size of the file
	function sizeHuman ()
	{
		return Filter::fileSize($this->size());
	}
	
	// Returns the size of the file in bytes
	function size ()
	{
		return filesize($this->file);
	}
	
	// Moves the file to a destination
	function move ($dest)
	{
		return $this->copy($dest) && $this->delete();
	}
	
	// Renames the file
	function rename ($name)
	{
		$name = basename($name);
		$dir = dirname($this->file);
		return @rename($this->file, $dir.'/'.$name);
	}
	
	// Forces the file to be downloaded in the browser
	function download ()
	{
		header('Content-Type: '.$this->mime());
		header('Content-length: '.$this->size());
		header(sprintf('Content-Disposition: attachment;file="%s"', addslashes($this->basename())));
		print $this->contents();
	}
	
	// Returns the file's contents or sets the file's contents
	function contents ($newContents = null)
	{
		if ($newContents !== null)
		{
			if (!is_file($this->file) || is_writable($this->file))
			{
				$f = fopen($this->file, 'w');
				fwrite($f, $newContents);
				fclose($f);
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return file_get_contents($this->file);
		}
	}
	
	// Uploads the file from AJAX input or a file header in the Request object
	function upload ($fileInfo = null)
	{
		if ($fileInfo !== null)
		{
			return $this->uploadFromRequest($fileInfo);
		}
		else
		{
			return $this->uploadFromInput();
		}
	}
	
	// Uploads a file from a Request file header
	function uploadFromRequest ($fileInfo)
	{
		return isset($fileInfo['tmp']) && @move_uploaded_file($fileInfo['tmp'], $this->file);
	}
	
	// Uploads a file from AJAX
	function uploadFromInput ()
	{
		return $this->contents(file_get_contents('php://input'));
	}
	
	// Returns the full path of the file
	function getFullPath ()
	{
		return $this->file;
	}
	
	// Returns true if the filename given is an actual file
	public static function isFile ($f)
	{
		return file_exists($f) && is_file($f);
	}
	
	// Returns the extension for a filename
	public static function getExtension ($filename)
	{
		return (new File($filename))->extension();
	}
}