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
class FileCache extends \CFileCache
{
	private $_gced = false;
	private $_originalKey;

	/**
	 * Override so we can set a custom file cache path.
	 */
	public function init()
	{
		if (!$this->cachePath)
		{
			$this->cachePath = craft()->path->getCachePath();
		}

		parent::init();
	}

	/**
	 * Stores a value identified by a key into cache.
	 * If the cache already contains such a key, the existing value and expiration time will be replaced with the new ones.
	 *
	 * @param  string $id                    The key identifying the value to be cached
	 * @param  mixed            $value       The value to be cached
	 * @param  integer          $expire      The number of seconds in which the cached value will expire. 0 means never expire.
	 * @param  \ICacheDependency $dependency Dependency of the cached item. If the dependency changes, the item is labeled invalid.
	 * @return boolean                       True if the value is successfully stored into cache, false otherwise
	 */
	public function set($id, $value, $expire = null, $dependency = null)
	{
		$this->_originalKey = $id;

		return parent::set($id, $value, $expire, $dependency);
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * Nothing will be done if the cache already contains the key.
	 *
	 * @param  string            $id         The key identifying the value to be cached
	 * @param  mixed             $value      The value to be cached
	 * @param  integer           $expire     The number of seconds in which the cached value will expire. 0 means never expire.
	 * @param  \ICacheDependency $dependency Dependency of the cached item. If the dependency changes, the item is labeled invalid.
	 * @return boolean                       True if the value is successfully stored into cache, false otherwise
	 */
	public function add($id, $value, $expire = null, $dependency = null)
	{
		$this->_originalKey = $id;

		return parent::add($id, $value, $expire, $dependency);
	}

	/**
	 * Stores a value identified by a key in cache. This is the implementation of the method declared in the parent class.
	 *
	 * @param  string  $key    The key identifying the value to be cached
	 * @param  string  $value  The value to be cached
	 * @param  integer $expire The number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true    If the value is successfully stored into cache, false otherwise
	 */
	protected function setValue($key, $value, $expire)
	{
		if (!$this->_gced && mt_rand(0, 1000000) < $this->getGCProbability())
		{
			$this->gc();
			$this->_gced = true;
		}

		if($expire <= 0)
		{
			$expire = 31536000; // 1 year
		}

		$expire += time();

		$cacheFile = $this->getCacheFile($key);

		if ($this->directoryLevel > 0)
		{
			IOHelper::createFolder(IOHelper::getFolderName($cacheFile), IOHelper::getWritableFolderPermissions());
		}

		if ($this->_originalKey == 'useWriteFileLock')
		{
			if (IOHelper::writeToFile($cacheFile, $value, true, false, true) !== false)
			{
				IOHelper::changePermissions($cacheFile, IOHelper::getWritableFilePermissions());
				return IOHelper::touch($cacheFile, $expire);
			}
			else
			{
				return false;
			}
		}
		else
		{
			if (IOHelper::writeToFile($cacheFile, $this->embedExpiry ? $expire.$value : $value) !== false)
			{
				IOHelper::changePermissions($cacheFile, IOHelper::getWritableFilePermissions());
				return $this->embedExpiry ? true : IOHelper::touch($cacheFile, $expire);
			}
			else
			{
				return false;
			}
		}
	}
}
