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
 * Section model class
 *
 * Used for transporting section data throughout the system.
 */
class SectionModel extends BaseModel
{
	private $_locales;
	private $_entryTypes;

	/**
	 * Use the translated section name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return Craft::t($this->name);
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'                        => AttributeType::Number,
			'structureId'               => AttributeType::Number,
			'name'                      => AttributeType::String,
			'handle'                    => AttributeType::String,
			'type'                      => array(AttributeType::Enum, 'values' => array(SectionType::Single, SectionType::Channel, SectionType::Structure)),
			'hasUrls'                   => array(AttributeType::Bool, 'default' => true),
			'template'                  => AttributeType::String,
			'maxLevels'                 => AttributeType::Number,
		);
	}

	/**
	 * Returns whether this is the homepage section.
	 *
	 * @return bool
	 */
	public function isHomepage()
	{
		return ($this->type == SectionType::Single && $this->urlFormat == '__home__');
	}

	/**
	 * Returns the section's locale models
	 *
	 * @return array
	 */
	public function getLocales()
	{
		if (!isset($this->_locales))
		{
			if ($this->id)
			{
				$this->_locales = craft()->sections->getSectionLocales($this->id, 'locale');
			}
			else
			{
				$this->_locales = array();
			}
		}

		return $this->_locales;
	}

	/**
	 * Sets the section's locale models.
	 *
	 * @param array $locales
	 */
	public function setLocales($locales)
	{
		$this->_locales = $locales;
	}

	/**
	 * Adds locale-specific errors to the model.
	 *
	 * @param array $errors
	 * @param string $localeId
	 */
	public function addLocaleErrors($errors, $localeId)
	{
		foreach ($errors as $attribute => $localeErrors)
		{
			$key = $attribute.'-'.$localeId;
			foreach ($localeErrors as $error)
			{
				$this->addError($key, $error);
			}
		}
	}

	/**
	 * Returns the section's entry types.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getEntryTypes($indexBy = null)
	{
		if (!isset($this->_entryTypes))
		{
			if ($this->id)
			{
				$this->_entryTypes = craft()->sections->getEntryTypesBySectionId($this->id);
			}
			else
			{
				$this->_entryTypes = array();
			}
		}

		if (!$indexBy)
		{
			return $this->_entryTypes;
		}
		else
		{
			$entryTypes = array();

			foreach ($this->_entryTypes as $entryType)
			{
				$entryTypes[$entryType->$indexBy] = $entryType;
			}

			return $entryTypes;
		}
	}

	/**
	 * Returns the section's URL format (or URL) for the current locale.
	 *
	 * @return string|null
	 */
	public function getUrlFormat()
	{
		$locales = $this->getLocales();

		if ($locales)
		{
			$localeIds = array_keys($locales);

			// Does this section target the current locale?
			if (in_array(craft()->language, $localeIds))
			{
				$localeId = craft()->language;
			}
			else
			{
				$localeId = $localeIds[0];
			}

			return $locales[$localeId]->urlFormat;
		}
	}
}
