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
class LogRouter extends \CLogRouter
{
	/**
	 * @param $route
	 */
	public function addRoute($route)
	{
		$counter = count($this->_routes);
		$route = Craft::createComponent($route);
		$route->init();
		$this->_routes[$counter] = $route;
	}

	/**
	 * Removes a route from the LogRouter by class name.
	 *
	 * @param $class
	 */
	public function removeRoute($class)
	{
		$match = false;

		for ($counter = 0; $counter < sizeof($this->_routes); $counter++)
		{
			if (StringHelper::toLowerCase(get_class($this->_routes[$counter])) == StringHelper::toLowerCase(__NAMESPACE__.'\\'.$class))
			{
				$match = $counter;
				break;
			}
		}

		if (is_numeric($match))
		{
			array_splice($this->_routes, $match, 1);
		}
	}
}
