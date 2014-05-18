<?php

/**
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 */

namespace Craft;

/**
 * Class BaseEnum
 *
 * @package Craft
 */
abstract class BaseEnum
{
	private static $_constants = null;

	/**
	 * @param      $name
	 * @param bool $strict
	 * @return bool
	 */
	public static function isValidName($name, $strict = false)
	{
		$constants = static::_getConstants();

		if ($strict)
		{
			return array_key_exists($name, $constants);
		}

		$keys = array_map(array('Craft\StringHelper', 'toLowerCase'), array_keys($constants));
		return in_array(StringHelper::toLowerCase($name), $keys);
	}

	/**
	 * @param      $value
	 * @param bool $strict
	 * @return bool
	 */
	public static function isValidValue($value, $strict = false)
	{
		$values = array_values(static::_getConstants());
		return in_array($value, $values, $strict);
	}

	/**
	 * @return null
	 */
	private static function _getConstants()
	{
		// static:: chokes PHP here because PHP sucks.
		if (self::$_constants === null)
		{
			$reflect = new \ReflectionClass(get_called_class());
			self::$_constants = $reflect->getConstants();
		}

		return self::$_constants;
	}
} 
