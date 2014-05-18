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
class ArrayHelper
{
	/**
	 * Flattens a multi-dimensional array into a single-dimensional array
	 *
	 * @static
	 * @param        $arr
	 * @param string $prefix
	 * @return array
	 */
	public static function flattenArray($arr, $prefix = null)
	{
		$flattened = array();

		foreach ($arr as $key => $value)
		{
			if ($prefix !== null)
			{
				$key = "{$prefix}[{$key}]";
			}

			if (is_array($value))
			{
				$flattened = array_merge($flattened, static::flattenArray($value, $key));
			}
			else
			{
				$flattened[$key] = $value;
			}
		}

		return $flattened;
	}

	/**
	 * Expands a flattened array back into its original form
	 *
	 * @static
	 * @param $arr
	 * @return array
	 */
	public static function expandArray($arr)
	{
		$expanded = array();

		foreach ($arr as $key => $value)
		{
			// is this an array element?
			if (preg_match('/^(\w+)(\[.*)/', $key, $m))
			{
				$key = '$expanded["'.$m[1].'"]' . preg_replace('/\[([a-zA-Z]\w*?)\]/', "[\"$1\"]", $m[2]);
				eval($key.' = "'.addslashes($value).'";');
			}
			else
			{
				$expanded[$key] = $value;
			}
		}

		return $expanded;
	}

	/**
	 * @static
	 * @param $settings
	 * @return array
	 */
	public static function expandSettingsArray($settings)
	{
		$arr = array();

		foreach ($settings as $setting)
		{
			$arr[$setting->name] = $setting->value;
		}

		return static::expandArray($arr);
	}

	/**
	 * Converts a comma-delimited string into a trimmed array
	 * ex: ArrayHelper::stringToArray('one, two, three') => array('one', 'two', 'three')
	 *
	 * @static
	 * @param mixed $str The string to convert to an array
	 * @return array The trimmed array
	 */
	public static function stringToArray($str)
	{
		if (is_array($str))
		{
			return $str;
		}
		else if ($str instanceof \ArrayObject)
		{
			return (array) $str;
		}
		else if (empty($str))
		{
			return array();
		}
		else if (is_string($str))
		{
			return array_merge(array_filter(array_map('trim', preg_split('/(?<!\\\),/', $str))));
		}
		else
		{
			return array($str);
		}
	}

	/**
	 * Prepends or appends a value to an array.
	 *
	 * @static
	 * @param array &$arr
	 * @param mixed $value
	 * @param bool  $prepend
	 */
	public static function prependOrAppend(&$arr, $value, $prepend)
	{
		if ($prepend)
		{
			array_unshift($arr, $value);
		}
		else
		{
			array_push($arr, $value);
		}
	}

	/**
	 * Filters empty strings from an array.
	 *
	 * @static
	 * @param array $arr
	 * @return array
	 */
	public static function filterEmptyStringsFromArray($arr)
	{
		return array_filter($arr, array('\Craft\ArrayHelper', '_isNotAnEmptyString'));
	}

	/**
	 * The array_filter() callback function for filterEmptyStringsFromArray().
	 *
	 * @static
	 * @access private
	 * @param $val
	 * @return bool
	 */
	private function _isNotAnEmptyString($val)
	{
		return (mb_strlen($val) != 0);
	}
}
