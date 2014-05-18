<?php
namespace Craft;

/**
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 */

/**
 *
 */
abstract class BaseIO
{
	protected $path;
	private $_realPath;
	private $_isReadable;
	private $_isWritable;
	private $_fullFolderName;
	private $_folderNameOnly;
	private $_lastTimeModified;
	private $_owner;
	private $_group;
	private $_permissions;

	/**
	 * @return mixed
	 */
	public function getRealPath()
	{
		if (!$this->_realPath)
		{
			$this->_realPath = IOHelper::getRealPath($this->path);
		}

		return $this->_realPath;
	}

	/**
	 * @return mixed
	 */
	public function isReadable()
	{
		if (!$this->_isReadable)
		{
			$this->_isReadable = IOHelper::isReadable($this->getRealPath());
		}

		return $this->_isReadable;
	}

	/**
	 * @return mixed
	 */
	public function isWritable()
	{
		if (!$this->_isWritable)
		{
			$this->_isWritable = IOHelper::isWritable($this->getRealPath());
		}

		return $this->_isWritable;
	}

	/**
	 * @return mixed
	 */
	public function getOwner()
	{
		if (!$this->_owner)
		{
			$this->_owner = IOHelper::getOwner($this->getRealPath());
		}

		return $this->_owner;
	}

	/**
	 * @return mixed
	 */
	public function getGroup()
	{
		if (!$this->_group)
		{
			$this->_group = IOHelper::getGroup($this->getRealPath());
		}

		return $this->_group;
	}

	/**
	 * @return mixed
	 */
	public function getPermissions()
	{
		if (!$this->_permissions)
		{
			$this->_permissions = IOHelper::getPermissions($this->getRealPath());
		}

		return $this->_permissions;
	}

	/**
	 * @return mixed
	 */
	public function getLastTimeModified()
	{
		if (!$this->_lastTimeModified)
		{
			$this->_lastTimeModified = IOHelper::getLastTimeModified($this->getRealPath());
		}

		return $this->_lastTimeModified;
	}

	/**
	 * @param $owner
	 * @return bool
	 */
	public function changeOwner($owner)
	{
		if (!IOHelper::changeOwner($this->getRealPath(), $owner))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $group
	 * @return bool
	 */
	public function changeGroup($group)
	{
		if (!IOHelper::changeGroup($this->getRealPath(), $group))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $permissions
	 * @return bool
	 */
	public function changePermissions($permissions)
	{
		if (!IOHelper::changePermissions($this->getRealPath(), $permissions))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $newName
	 * @return bool
	 */
	public function rename($newName)
	{
		if (!IOHelper::rename($this->getRealPath(), $newName))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param $newPath
	 * @return bool
	 */
	public function move($newPath)
	{
		if (!IOHelper::move($this->getRealPath(), $newPath))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param bool $fullPath
	 * @return mixed
	 */
	public function getFolderName($fullPath = true)
	{
		if ($fullPath)
		{
			if (!$this->_fullFolderName)
			{
				$this->_fullFolderName = IOHelper::getFolderName($this->getRealPath(), $fullPath);
			}

			return $this->_fullFolderName;
		}
		else
		{
			if (!$this->_folderNameOnly)
			{
				$this->_folderNameOnly = IOHelper::getFolderName($this->getRealPath(), $fullPath);
			}

			return $this->_folderNameOnly;
		}
	}
}
