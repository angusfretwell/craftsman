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
class LocalizationService extends BaseApplicationComponent
{
	private $_appLocales;
	private $_siteLocales;
	private $_localeData;

	/**
	 * Returns of all known locales.
	 *
	 * @return array
	 */
	public function getAllLocales()
	{
		$locales = array();
		$localeIds = LocaleData::getLocaleIds();

		foreach ($localeIds as $localeId)
		{
			$locales[] = new LocaleModel($localeId);
		}

		return $locales;
	}

	/**
	 * Returns a list of language ids from the languages directory that Craft is translated into.
	 *
	 * @return mixed
	 */
	public function getAppLocales()
	{
		if (!$this->_appLocales)
		{
			$this->_appLocales = array(new LocaleModel('en_us'));

			$path = craft()->path->getCpTranslationsPath();
			$folders = IOHelper::getFolderContents($path, false, ".*\.php");

			if (is_array($folders) && count($folders) > 0)
			{
				foreach ($folders as $dir)
				{
					$localeId = IOHelper::getFileName($dir, false);
					if ($localeId != 'en_us')
					{
						$this->_appLocales[] = new LocaleModel($localeId);
					}
				}
			}
		}

		return $this->_appLocales;
	}

	/**
	 * Returns an array of the locale IDs which Craft has been translated into.
	 *
	 * @return array
	 */
	public function getAppLocaleIds()
	{
		$locales = $this->getAppLocales();
		$localeIds = array();

		foreach ($locales as $locale)
		{
			$localeIds[] = $locale->id;
		}

		return $localeIds;
	}

	/**
	 * Returns the locales that the site is translated for.
	 *
	 * @return array
	 */
	public function getSiteLocales()
	{
		if (!isset($this->_siteLocales))
		{
			$query = craft()->db->createCommand()
				->select('locale')
				->from('locales')
				->order('sortOrder');

			if (craft()->getEdition() != Craft::Pro)
			{
				$query->limit(1);
			}

			$localeIds = $query->queryColumn();

			foreach ($localeIds as $localeId)
			{
				$this->_siteLocales[] = new LocaleModel($localeId);
			}

			if (empty($this->_siteLocales))
			{
				$this->_siteLocales = array(new LocaleModel('en_us'));
			}
		}

		return $this->_siteLocales;
	}

	/**
	 * Returns the site's primary locale.
	 *
	 * @return LocaleModel
	 */
	public function getPrimarySiteLocale()
	{
		$locales = $this->getSiteLocales();
		return $locales[0];
	}

	/**
	 * Returns the site's primary locale ID.
	 *
	 * @return string
	 */
	public function getPrimarySiteLocaleId()
	{
		return $this->getPrimarySiteLocale()->getId();
	}

	/**
	 * Returns an array of the site locale IDs.
	 *
	 * @return array
	 */
	public function getSiteLocaleIds()
	{
		$locales = $this->getSiteLocales();
		$localeIds = array();

		foreach ($locales as $locale)
		{
			$localeIds[] = $locale->id;
		}

		return $localeIds;
	}

	/**
	 * Returns a list of locales that are editable by the current user.
	 *
	 * @return array
	 */
	public function getEditableLocales()
	{
		if (craft()->isLocalized())
		{
			$locales = $this->getSiteLocales();
			$editableLocales = array();

			foreach ($locales as $locale)
			{
				if (craft()->userSession->checkPermission('editLocale:'.$locale->getId()))
				{
					$editableLocales[] = $locale;
				}
			}

			return $editableLocales;
		}
		else
		{
			return $this->getSiteLocales();
		}
	}

	/**
	 * Returns an array of the editable locale IDs.
	 *
	 * @return array
	 */
	public function getEditableLocaleIds()
	{
		$locales = $this->getEditableLocales();
		$localeIds = array();

		foreach ($locales as $locale)
		{
			$localeIds[] = $locale->id;
		}

		return $localeIds;
	}

	/**
	 * Returns a locale by its ID.
	 *
	 * @param string $localeId
	 * @return LocaleModel
	 */
	public function getLocaleById($localeId)
	{
		return new LocaleModel($localeId);
	}

	/**
	 * Adds a new site locale.
	 *
	 * @param string $localeId
	 * @return bool
	 */
	public function addSiteLocale($localeId)
	{
		$maxSortOrder = craft()->db->createCommand()->select('max(sortOrder)')->from('locales')->queryScalar();
		$affectedRows = craft()->db->createCommand()->insert('locales', array('locale' => $localeId, 'sortOrder' => $maxSortOrder+1));
		$success = (bool) $affectedRows;

		if ($success)
		{
			$this->_siteLocales[] = new LocaleModel($localeId);

			// Add this locale to each of the category groups
			$categoryLocales = craft()->db->createCommand()
				->select('groupId, urlFormat, nestedUrlFormat')
				->from('categorygroups_i18n')
				->where('locale = :locale', array(':locale' => $this->getPrimarySiteLocaleId()))
				->queryAll();

			if ($categoryLocales)
			{
				$newCategoryLocales = array();

				foreach ($categoryLocales as $categoryLocale)
				{
					$newCategoryLocales[] = array($categoryLocale['groupId'], $localeId, $categoryLocale['urlFormat'], $categoryLocale['nestedUrlFormat']);
				}

				craft()->db->createCommand()->insertAll('categorygroups_i18n', array('groupId', 'locale', 'urlFormat', 'nestedUrlFormat'), $newCategoryLocales);
			}

			// Resave all of the localizable elements
			if (!craft()->tasks->areTasksPending('ResaveAllElements'))
			{
				craft()->tasks->createTask('ResaveAllElements', null, array(
					'localizableOnly' => true,
				));
			}
		}

		return $success;
	}

	/**
	 * Reorders the site's locales.
	 *
	 * @param array $localeIds
	 * @return bool
	 */
	public function reorderSiteLocales($localeIds)
	{
		$oldPrimaryLocaleId = $this->getPrimarySiteLocaleId();

		foreach ($localeIds as $sortOrder => $localeId)
		{
			craft()->db->createCommand()->update('locales', array('sortOrder' => $sortOrder+1), array('locale' => $localeId));
		}

		$this->_siteLocales = null;
		$newPrimaryLocaleId = $this->getPrimarySiteLocaleId();

		// Did the primary site locale just change?
		if ($oldPrimaryLocaleId != $newPrimaryLocaleId)
		{
			craft()->config->maxPowerCaptain();

			// Update all of the non-localized elements
			$nonLocalizedElementTypes = array();

			foreach (craft()->elements->getAllElementTypes() as $elementType)
			{
				if (!$elementType->isLocalized())
				{
					$nonLocalizedElementTypes[] = $elementType->getClassHandle();
				}
			}

			if ($nonLocalizedElementTypes)
			{
				$elementIds = craft()->db->createCommand()
					->select('id')
					->from('elements')
					->where(array('in', 'type', $nonLocalizedElementTypes))
					->queryColumn();

				if ($elementIds)
				{
					// To be sure we don't hit any unique constraint MySQL errors,
					// first make sure there are no rows for these elements that don't currently use the old primary locale
					$deleteConditions = array('and', array('in', 'elementId', $elementIds), 'locale != :locale');
					$deleteParams = array(':locale' => $oldPrimaryLocaleId);

					craft()->db->createCommand()->delete('elements_i18n', $deleteConditions, $deleteParams);
					craft()->db->createCommand()->delete('content', $deleteConditions, $deleteParams);

					// Now convert the locales
					$updateColumns = array('locale' => $newPrimaryLocaleId);
					$updateConditions = array('in', 'elementId', $elementIds);

					craft()->db->createCommand()->update('elements_i18n', $updateColumns, $updateConditions);
					craft()->db->createCommand()->update('content', $updateColumns, $updateConditions);
				}
			}
		}

		return true;
	}

	/**
	 * Deletes a site locale.
	 *
	 * @param string $localeId
	 * @return bool
	 */
	public function deleteSiteLocale($localeId)
	{
		$affectedRows = craft()->db->createCommand()->delete('locales', array('locale' => $localeId));
		return (bool) $affectedRows;
	}

	/**
	 * Returns the localization data for a given locale.
	 *
	 * @param $localeId
	 * @return LocaleData|null
	 */
	public function getLocaleData($localeId = null)
	{
		if (!$localeId)
		{
			$localeId = craft()->language;
		}

		if (!isset($this->_localeData) || !array_key_exists($localeId, $this->_localeData))
		{
			if (LocaleData::exists($localeId))
			{
				$this->_localeData[$localeId] = LocaleData::getInstance($localeId);
			}
			else
			{
				$this->_localeData[$localeId] = null;
			}
		}

		return $this->_localeData[$localeId];
	}
}
