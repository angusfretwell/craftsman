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
 * Helper class for template variables
 */
class VariableHelper
{
	/**
	 * Returns an array of variables for a given set of class instances.
	 *
	 * @static
	 * @param array $instances
	 * @param string $class
	 * @return array
	 */
	public static function populateVariables($instances, $class)
	{
		$variables = array();

		if (is_array($instances))
		{
			$namespace = __NAMESPACE__.'\\';
			if (strncmp($class, $namespace, mb_strlen($namespace)) != 0)
			{
				$class = $namespace.$class;
			}

			foreach ($instances as $key => $instance)
			{
				$variables[$key] = new $class($instance);
			}
		}

		return $variables;
	}
}
