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
class CacheService extends BaseApplicationComponent
{
	private $_cacheComponent = null;

	/**
	 * Do the ole' craft()->cache switcharoo.
	 */
	public function init()
	{
		switch (craft()->config->get('cacheMethod'))
		{
			case CacheMethod::APC:
			{
				$this->_cacheComponent = new ApcCache();
				break;
			}

			case CacheMethod::Db:
			{
				$this->_cacheComponent = new DbCache();
				$this->_cacheComponent->gCProbability = craft()->config->get('gcProbability', ConfigFile::DbCache);
				$this->_cacheComponent->cacheTableName = craft()->db->getNormalizedTablePrefix().craft()->config->get('cacheTableName', ConfigFile::DbCache);;
				$this->_cacheComponent->autoCreateCacheTable = true;
				break;
			}

			case CacheMethod::EAccelerator:
			{
				$this->_cacheComponent = new EAcceleratorCache();
				break;
			}

			case CacheMethod::File:
			{
				$this->_cacheComponent = new FileCache();
				$this->_cacheComponent->cachePath = craft()->config->get('cachePath', ConfigFile::FileCache);
				$this->_cacheComponent->gCProbability = craft()->config->get('gcProbability', ConfigFile::FileCache);
				break;
			}

			case CacheMethod::MemCache:
			{
				$this->_cacheComponent = new MemCache();
				$this->_cacheComponent->servers = craft()->config->get('servers', ConfigFile::Memcache);
				$this->_cacheComponent->useMemcached = craft()->config->get('useMemcached', ConfigFile::Memcache);
				break;
			}

			case CacheMethod::Redis:
			{
				$this->_cacheComponent = new RedisCache();
				$this->_cacheComponent->hostname = craft()->config->get('hostname', ConfigFile::RedisCache);
				$this->_cacheComponent->port = craft()->config->get('port', ConfigFile::RedisCache);
				$this->_cacheComponent->password = craft()->config->get('password', ConfigFile::RedisCache);
				$this->_cacheComponent->database = craft()->config->get('database', ConfigFile::RedisCache);
				$this->_cacheComponent->timeout = craft()->config->get('timeout', ConfigFile::RedisCache);
				break;
			}

			case CacheMethod::WinCache:
			{
				$this->_cacheComponent = new WinCache();
				break;
			}

			case CacheMethod::XCache:
			{
				$this->_cacheComponent = new XCache();
				break;
			}

			case CacheMethod::ZendData:
			{
				$this->_cacheComponent = new ZendDataCache();
				break;
			}
		}

		// Init the cache component.
		$this->_cacheComponent->init();

		// Init the cache service.
		parent::init();
	}

	/**
	 * Stores a value identified by a key into cache.  If the cache already contains such a key, the existing value and expiration time will be replaced with the new ones.
	 *
	 * @param string $id the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @param \ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labeled invalid.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function set($id, $value, $expire = null, $dependency = null)
	{
		if ($expire === null)
		{
			$expire = craft()->config->getCacheDuration();
		}

		return $this->_cacheComponent->set($id, $value, $expire, $dependency);
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.  Nothing will be done if the cache already contains the key.
	 *
	 * @param string $id the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @param \ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labeled invalid.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function add($id, $value, $expire = null, $dependency = null)
	{
		if ($expire === null)
		{
			$expire = craft()->config->getCacheDuration();
		}

		return $this->_cacheComponent->add($id, $value, $expire, $dependency);
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 *
	 * @param string $id a key identifying the cached value
	 * @return mixed the value stored in cache, false if the value is not in the cache, expired or the dependency has changed.
	 */
	public function get($id)
	{
		return $this->_cacheComponent->get($id);
	}

	/**
	 * Retrieves multiple values from cache with the specified keys.
	 * Some caches (such as memcache, apc) allow retrieving multiple cached values at one time,
	 * which may improve the performance since it reduces the communication cost.
	 * In case a cache does not support this feature natively, it will be simulated by this method.
	 *
	 * @param array $ids list of keys identifying the cached values
	 * @return array list of cached values corresponding to the specified keys. The array is returned in terms of (key,value) pairs.
	 * If a value is not cached or expired, the corresponding array value will be false.
	 */
	public function mget($ids)
	{
		return $this->_cacheComponent->mget($ids);
	}

	/**
	 * Deletes a value with the specified key from cache
	 *
	 * @param string $id the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	public function delete($id)
	{
		return $this->_cacheComponent->delete($id);
	}

	/**
	 * Deletes all values from cache. Be careful of performing this operation if the cache is shared by multiple applications.
	 *
	 * @return boolean whether the flush operation was successful.
	 */
	public function flush()
	{
		return $this->_cacheComponent->flush();
	}
}
