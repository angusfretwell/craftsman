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
 * MemCache implements a cache application component based on {@link http://memcached.org/ memcached}.
 *
 * MemCache can be configured with a list of memcache servers.  By default, MemCache assumes
 * there is a memcache server running on localhost at port 11211.
 *
 * Note, there is no security measure to protected data in memcache.
 * All data in memcache can be accessed by any process running in the system.

 * MemCache can also be used with {@link http://pecl.php.net/package/memcached memcached}.
 * To do so, set useMemcached to be true.
 */
class MemCache extends \CMemCache
{

}
