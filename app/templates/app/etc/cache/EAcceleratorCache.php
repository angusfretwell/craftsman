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
 * EAcceleratorCache implements a cache application module based on {@link http://eaccelerator.net/ eaccelerator}.
 *
 * To use this application component, the eAccelerator PHP extension must be loaded.
 *
 * Please note that as of v0.9.6, eAccelerator no longer supports data caching.
 *
 * This means if you still want to use this component, your eAccelerator should be of 0.9.5.x or lower version.
 */
class EAcceleratorCache extends \CEAcceleratorCache
{

}
