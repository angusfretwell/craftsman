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
class RecentEntriesWidget extends BaseWidget
{
	public $multipleInstances = true;

	/**
	 * Returns the type of widget this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Recent Entries');
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'section' => array(AttributeType::Mixed, 'default' => '*'),
			'limit'   => array(AttributeType::Number, 'default' => 10),
		);
	}

	/**
	 * Returns the widget's body HTML.
	 *
	 * @return string
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->render('_components/widgets/RecentEntries/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Gets the widget's title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		if (craft()->getEdition() >= Craft::Client)
		{
			$sectionId = $this->getSettings()->section;

			if (is_numeric($sectionId))
			{
				$section = craft()->sections->getSectionById($sectionId);

				if ($section)
				{
					return Craft::t('Recently in {section}', array('section' => $section->name));
				}
			}
		}

		return Craft::t('Recent Entries');
	}

	/**
	 * Returns the widget's body HTML.
	 *
	 * @return string|false
	 */
	public function getBodyHtml()
	{
		$params = array();

		if (craft()->getEdition() >= Craft::Client)
		{
			$sectionId = $this->getSettings()->section;

			if (is_numeric($sectionId))
			{
				$params['sectionId'] = (int)$sectionId;
			}
		}

		$js = 'new Craft.RecentEntriesWidget('.$this->model->id.', '.JsonHelper::encode($params).');';

		craft()->templates->includeJsResource('js/RecentEntriesWidget.js');
		craft()->templates->includeJs($js);
		craft()->templates->includeTranslations('by {author}');

		$entries = $this->_getEntries();

		return craft()->templates->render('_components/widgets/RecentEntries/body', array(
			'entries' => $entries
		));
	}

	/**
	 * Returns the recent entries, based on the widget settings and user permissions.
	 *
	 * @access private
	 * @return array
	 */
	private function _getEntries()
	{
		// Make sure that the user is actually allowed to edit entries in the current locale.
		// Otherwise grab entries in their first editable locale.
		$editableLocaleIds = craft()->i18n->getEditableLocaleIds();
		$targetLocale = craft()->language;

		if (!$editableLocaleIds)
		{
			return array();
		}

		if (!in_array($targetLocale, $editableLocaleIds))
		{
			$targetLocale = $editableLocaleIds[0];
		}

		// Normalize the target section ID value.
		$editableSectionIds = $this->_getEditableSectionIds();
		$targetSectionId = $this->getSettings()->section;

		if (!$targetSectionId || $targetSectionId == '*' || !in_array($targetSectionId, $editableSectionIds))
		{
			$targetSectionId = array_merge($editableSectionIds);
		}

		if (!$targetSectionId)
		{
			return array();
		}

		$criteria = craft()->elements->getCriteria(ElementType::Entry);
		$criteria->status = null;
		$criteria->localeEnabled = null;
		$criteria->locale = $targetLocale;
		$criteria->sectionId = $targetSectionId;
		$criteria->editable = true;
		$criteria->limit = $this->getSettings()->limit;
		$criteria->order = 'dateCreated desc';

		return $criteria->find();
	}

	/**
	 * Returns the Channel and Structure section IDs that the user is allowed to edit.
	 *
	 * @access private
	 * @return array
	 */
	private function _getEditableSectionIds()
	{
		$sectionIds = array();

		foreach (craft()->sections->getEditableSections() as $section)
		{
			if ($section->type != SectionType::Single)
			{
				$sectionIds[] = $section->id;
			}
		}

		return $sectionIds;
	}
}
