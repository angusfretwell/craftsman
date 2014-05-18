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
 * Represents info for an uploaded file.
 */
class UploadedFile extends \CUploadedFile
{
	/**
	 * Returns an instance of the specified uploaded file.
	 *
	 * @static
	 * @param string $name
	 * @return \CUploadedFile|null
	 */
	public static function getInstanceByName($name)
	{
		$name = static::_normalizeName($name);
		return parent::getInstanceByName($name);
	}

	/**
	 * Returns an array of instances starting with specified array name.
	 *
	 * @param string $name
	 * @param bool $lookForSingleInstance
	 * @return array
	 */
	public static function getInstancesByName($name, $lookForSingleInstance = true)
	{
		$name = static::_normalizeName($name);
		$instances = parent::getInstancesByName($name);

		if (!$instances && $lookForSingleInstance)
		{
			$singleInstance = parent::getInstanceByName($name);

			if ($singleInstance)
			{
				$instances[] = $singleInstance;
			}
		}

		return $instances;
	}

	/**
	 * Swaps dot notation for the normal format.
	 *
	 * ex: fields.assetsField => fields[assetsField]
	 *
	 * @static
	 * @access private
	 * @param string $name
	 * @return string
	 */
	private static function _normalizeName($name)
	{
		if (($pos = strpos($name, '.')) !== false)
		{
			// Convert dot notation to the normal format
			// ex: fields.assetsField => fields[assetsField]
			$name = substr($name, 0, $pos).'['.str_replace('.', '][', substr($name, $pos+1)).']';
		}

		return $name;
	}
}
