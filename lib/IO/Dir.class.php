<?php

/**
 * Dir
 *
 * Encapsulates directory access, iteration, and file system operations that
 * involve directory creation, modification, or removal.
 */

namespace Base\IO;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \DirectoryIterator;
use Base\Exception;

class Dir
{
	// Directory
	protected $dir;
	
	// Sorting constants
	const FILENAME = 0;
	const FILENAME_DESC = 1;
	const SIZE = 2;
	const SIZE_DESC = 3;
	
	
	// Constructor
	function __construct ($path)
	{
		$this->dir = $path;
	}
	
	// Creates the new directory
	function create ($perms = 0644)
	{
		return @mkdir($this->dir, $perms, true);
	}
	
	// Removes the directory
	function remove ($recursive = true)
	{
		if ($recursive === true)
		{
			$dir = new RecursiveDirectoryIterator($this->dir);
			$files = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);
			foreach ($files as $obj)
			{
				if ($obj->isDir())
				{
					@rmdir($obj->getPathname());
				}
				else
				{
					@unlink($obj->getPathname());
				}
			}
			
			// Finally remove the directory
			return @rmdir($this->dir);
		}
		else
		{
			$dirs = new DirectoryIterator($this->dir);
			foreach ($dirs as $obj)
			{
				if ($obj->isFile())
				{
					@unlink($obj->getPathname());
				}
			}
			return true;
		}
	}
	
	// Generates an iterator for a directory
	public static function iterate ($dir, $recursive = false)
	{
		if ($recursive === true)
		{
			$iterator = new RecursiveDirectoryIterator($dir);
			return new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
		}
		else
		{
			return new DirectoryIterator($dir);
		}
	}
	
	// Returns true if the directory exists
	function exists ()
	{
		return file_exists($this->dir) && is_dir($this->dir);
	}
	
	// Copies the directory to a destination
	function copy ($dest, $recursive = false)
	{
		$dir = new RecursiveDirectoryIterator($this->dir);
		$files = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::SELF_FIRST);
		foreach ($files as $obj)
		{
			$dest = $dest.'/'.$files->getSubPathName();
			if ($obj->isDir())
			{
				mkdir($dest, $obj->getPerms());
			}
			else
			{
				copy($obj->getPathname(), $dest);
			}
		}
		return true;
	}
	
	// Returns the basename for the directory
	function basename ()
	{
		return basename($this->dir);
	}
	
	// Moves the directory to a destination
	function move ($dest)
	{
		return $this->copy($dest) && $this->remove(true);
	}
	
	// Renames the directory
	function rename ($dest)
	{
		$dest = basename($dest);
		return rename($this->dir, dirname($this->dir).'/'.$dest);
	}
	
	// Lists all files in the directory
	function listAll ($sortBy = self::FILENAME)
	{
		$result = array();
		
		$files = new DirectoryIterator($this->dir);
		foreach ($files as $obj)
		{
			if ($obj->isFile() || ($obj->getBasename() != '.' && $obj->getBasename() != '..'))
			{
				$result[] = ['size' => $obj->getSize(), 'name' => $obj->getBasename(), 'info' => clone $obj];
			}
		}
		
		switch ($sortBy)
		{
			case self::SIZE:
				usort($result, function ($row1, $row2) {
					if ($row1['info']->isDir() === $row2['info']->isDir())
					{
						return $row1['size'] < $row2['size'] ? -1 : 1;
					}
					else
					{
						return $row1['info']->isDir() ? -1 : 1;
					}
				});
				return $result;
			case self::SIZE_DESC:
				usort($result, function ($row1, $row2) {
					if ($row1['info']->isDir() === $row2['info']->isDir())
					{
						return $row1['size'] > $row2['size'] ? -1 : 1;
					}
					else
					{
						return $row1['info']->isDir() ? 1 : -1;
					}
				});
				return $result;
			case self::FILENAME_DESC:
				usort($result, function ($row1, $row2) {
					if ($row1['info']->isDir() === $row2['info']->isDir())
					{
						return -strcmp($row1['name'], $row2['name']);
					}
					else
					{
						return $row1['info']->isDir() ? 1 : -1;
					}
				});
				return $result;
			case self::FILENAME:
				usort($result, function ($row1, $row2) {
					if ($row1['info']->isDir() === $row2['info']->isDir())
					{
						return strcmp($row1['name'], $row2['name']);
					}
					else
					{
						return $row1['info']->isDir() ? -1 : 1;
					}
				});
				return $result;
			default:
				return $result;
		}
	}
	
	// Returns the directory path
	function getDir ()
	{
		return $this->dir;
	}
	
	// Returns the size of the directory in bytes
	function size ($recursive = false)
	{
		if (!$this->exists())
		{
			throw new Exception('Directory Not Found', 'The specified directory could not be traversed as it does not exist');
		}
		$size = 0;
		if ($recursive === true)
		{
			$dir = new RecursiveDirectoryIterator($this->dir);
			$files = new RecursiveIteratorIterator($dir);
		}
		else
		{
			$files = new DirectoryIterator($this->dir);
		}
		
		foreach ($files as $obj)
		{
			if ($obj->isFile())
			{
				$size += $obj->getSize();
			}
		}
		return $size;
	}
	
	// Returns true if a directory is writable
	function isWritable ()
	{
		return is_writable($this->dir);
	}
	
	// Returns the size of a directory in bytes
	public static function getSize ($path, $recursive = false)
	{
		$d = new Dir($path);
		return $d->size($recursive);
	}
}