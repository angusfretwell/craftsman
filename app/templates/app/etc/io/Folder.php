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
class Folder extends BaseIO
{
	private $_size;
	private $_isEmpty;

	/**
	 * @param $path
	 */
	public function __construct($path)
	{
		clearstatcache();
		$this->path = $path;
	}

	/**
	 * @return mixed
	 */
	public function getSize()
	{
		if (!$this->_size)
		{
			$this->_size = IOHelper::getFolderSize($this->getRealPath());
		}

		return $this->_size;
	}

	/**
	 * @return mixed
	 */
	public function isEmpty()
	{
		if (!$this->_isEmpty)
		{
			$this->_isEmpty = IOHelper::isFolderEmpty($this->getRealPath());
		}

		return $this->_isEmpty;
	}

	/**
	 * @param $recursive
	 * @param $filter
	 * @return mixed
	 */
	public function getContents($recursive, $filter)
	{
		return IOHelper::getFolderContents($this->getRealPath(), $recursive, $filter);
	}

	/**
	 * @param $destination
	 * @return bool
	 */
	public function copy($destination)
	{
		if (!IOHelper::copyFolder($this->getRealPath(), $destination))
		{
			return false;
		}

		return true;
	}

	/**
	 * @param bool $suppressErrors
	 * @return bool
	 */
	public function clear($suppressErrors = false)
	{
		if (!IOHelper::clearFolder($this->getRealPath(), $suppressErrors))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function delete()
	{
		if (!IOHelper::deleteFolder($this->getRealPath()))
		{
			return false;
		}

		return true;
	}
}
