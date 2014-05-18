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
class LocaleData extends \CLocale
{
	/**
	 * Returns the instance of the specified locale. Since the constructor of CLocale is protected, you can only use
	 * this method to obtain an instance of the specified locale.
	 *
	 * @param  string $id The locale ID (e.g. en_US)
	 * @return LocaleData The locale instance
	 */
	public static function getInstance($id)
	{
		static $locales = array();

		if (isset($locales[$id]))
		{
			return $locales[$id];
		}
		else
		{
			return $locales[$id] = new LocaleData($id);
		}
	}

	/**
	 * Overriding getLanguage() from \CLocale because this is where we do want to chop off the territory half of a locale ID.
	 */
	public function getLanguage($id)
	{
		$id = $this->getLanguageID($id);
		return $this->getLocaleDisplayName($id, 'languages');
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public static function exists($id)
	{
		$id = static::getCanonicalID($id);
		$dataPath = static::$dataPath === null ? craft()->path->getFrameworkPath().'i18n/data' : static::$dataPath;
		$dataFile = $dataPath.'/'.$id.'.php';

		return IOHelper::fileExists($dataFile);
	}

	/**
	 * @return NumberFormatter
	 */
	public function getNumberFormatter()
	{
		if ($this->_numberFormatter === null)
		{
			$this->_numberFormatter = new NumberFormatter($this);
		}

		return $this->_numberFormatter;
	}

	/**
	 * @return DateFormatter
	 */
	public function getDateFormatter()
	{
		if ($this->_dateFormatter === null)
		{
			$this->_dateFormatter = new DateFormatter($this);
		}

		return $this->_dateFormatter;
	}

}
