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
class ContentService extends BaseApplicationComponent
{
	public $contentTable = 'content';
	public $fieldColumnPrefix = 'field_';
	public $fieldContext = 'global';

	/**
	 * Returns the content model for a given element.
	 *
	 * @param BaseElementModel $elementId
	 * @return ContentModel|null
	 */
	public function getContent(BaseElementModel $element)
	{
		if (!$element->id || !$element->locale)
		{
			return;
		}

		$originalContentTable      = $this->contentTable;
		$originalFieldColumnPrefix = $this->fieldColumnPrefix;
		$originalFieldContext      = $this->fieldContext;

		$this->contentTable        = $element->getContentTable();
		$this->fieldColumnPrefix   = $element->getFieldColumnPrefix();
		$this->fieldContext        = $element->getFieldContext();

		$row = craft()->db->createCommand()
			->from($this->contentTable)
			->where(array(
				'elementId' => $element->id,
				'locale'    => $element->locale
			))
			->queryRow();

		if ($row)
		{
			$row = $this->_removeColumnPrefixesFromRow($row);
			$content = new ContentModel($row);
		}
		else
		{
			$content = null;
		}

		$this->contentTable        = $originalContentTable;
		$this->fieldColumnPrefix   = $originalFieldColumnPrefix;
		$this->fieldContext        = $originalFieldContext;

		return $content;
	}

	/**
	 * Creates a new content model for a given element.
	 *
	 * @param BaseElementModel $element
	 * @return ContentModel
	 */
	public function createContent(BaseElementModel $element)
	{
		$originalContentTable      = $this->contentTable;
		$originalFieldColumnPrefix = $this->fieldColumnPrefix;
		$originalFieldContext      = $this->fieldContext;

		$this->contentTable        = $element->getContentTable();
		$this->fieldColumnPrefix   = $element->getFieldColumnPrefix();
		$this->fieldContext        = $element->getFieldContext();

		$content = new ContentModel();
		$content->elementId = $element->id;
		$content->locale = $element->locale;

		$this->contentTable        = $originalContentTable;
		$this->fieldColumnPrefix   = $originalFieldColumnPrefix;
		$this->fieldContext        = $originalFieldContext;

		return $content;
	}

	/**
	 * Saves an element's content.
	 *
	 * @param BaseElementModel $element
	 * @param bool             $validate
	 * @param bool             $updateOtherLocales
	 * @throws Exception
	 * @return bool
	 */
	public function saveContent(BaseElementModel $element, $validate = true, $updateOtherLocales = true)
	{
		if (!$element->id)
		{
			throw new Exception(Craft::t('Cannot save the content of an unsaved element.'));
		}

		$originalContentTable      = $this->contentTable;
		$originalFieldColumnPrefix = $this->fieldColumnPrefix;
		$originalFieldContext      = $this->fieldContext;

		$this->contentTable        = $element->getContentTable();
		$this->fieldColumnPrefix   = $element->getFieldColumnPrefix();
		$this->fieldContext        = $element->getFieldContext();

		$content = $element->getContent();

		if (!$validate || $this->validateContent($element))
		{
			$this->_saveContentRow($content);

			$fieldLayout = $element->getFieldLayout();

			if ($fieldLayout)
			{
				if ($updateOtherLocales && craft()->isLocalized())
				{
					$this->_duplicateNonTranslatableFieldValues($element, $content, $fieldLayout, $nonTranslatableFields, $otherContentModels);
				}

				$this->_updateSearchIndexes($element, $content, $fieldLayout, $nonTranslatableFields, $otherContentModels);
			}

			$success = true;
		}
		else
		{
			$element->addErrors($content->getErrors());
			$success = false;
		}

		$this->contentTable        = $originalContentTable;
		$this->fieldColumnPrefix   = $originalFieldColumnPrefix;
		$this->fieldContext        = $originalFieldContext;

		return $success;
	}

	/**
	 * Validates some content with a given field layout.
	 *
	 * @param BaseElementModel $element
	 * @return bool
	 */
	public function validateContent(BaseElementModel $element)
	{
		$elementType = craft()->elements->getElementType($element->getElementType());
		$fieldLayout = $element->getFieldLayout();
		$content     = $element->getContent();

		// Set the required fields from the layout
		$attributesToValidate = array('id', 'elementId', 'locale');
		$requiredFields = array();

		if ($elementType->hasTitles())
		{
			$requiredFields[] = 'title';
			$attributesToValidate[] = 'title';
		}

		if ($fieldLayout)
		{
			foreach ($fieldLayout->getFields() as $fieldLayoutField)
			{
				$field = $fieldLayoutField->getField();

				if ($field)
				{
					$attributesToValidate[] = $field->handle;

					if ($fieldLayoutField->required)
					{
						$requiredFields[] = $field->id;
					}
				}
			}
		}

		if ($requiredFields)
		{
			$content->setRequiredFields($requiredFields);
		}

		return $content->validate($attributesToValidate);
	}

	/**
	 * Fires an 'onSaveContent' event.
	 *
	 * @param Event $event
	 */
	public function onSaveContent(Event $event)
	{
		$this->raiseEvent('onSaveContent', $event);
	}

	/**
	 * Saves a content model to the database.
	 *
	 * @access private
	 * @param ContentModel $content
	 * @return bool
	 */
	private function _saveContentRow(ContentModel $content)
	{
		$values = array(
			'id'        => $content->id,
			'elementId' => $content->elementId,
			'locale'    => $content->locale,
		);

		// If the element type has titles, than it's required and will be set.
		// Otherwise, no need to include it (it might not even be a real column if this isn't the 'content' table).
		if ($content->title)
		{
			$values['title'] = $content->title;
		}

		foreach (craft()->fields->getFieldsWithContent() as $field)
		{
			$handle = $field->handle;
			$value = $content->$handle;
			$values[$this->fieldColumnPrefix.$field->handle] = ModelHelper::packageAttributeValue($value, true);
		}

		$isNewContent = !$content->id;

		if (!$isNewContent)
		{
			$affectedRows = craft()->db->createCommand()->update($this->contentTable, $values, array('id' => $content->id));
		}
		else
		{
			$affectedRows = craft()->db->createCommand()->insert($this->contentTable, $values);
		}

		if ($affectedRows)
		{
			if ($isNewContent)
			{
				// Set the new ID
				$content->id = craft()->db->getLastInsertID();
			}

			// Fire an 'onSaveContent' event
			$this->onSaveContent(new Event($this, array(
				'content'      => $content,
				'isNewContent' => $isNewContent
			)));

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Copies the new values of any non-translatable fields across the element's other locales.
	 *
	 * @access private
	 * @param BaseElementModel $element
	 * @param ContentModel     $content
	 * @param FieldLayoutModel $fieldLayout
	 * @param array            &$nonTranslatableFields
	 * @param array            &$otherContentModels
	 * @param
	 */
	private function _duplicateNonTranslatableFieldValues(BaseElementModel $element, ContentModel $content, FieldLayoutModel $fieldLayout, &$nonTranslatableFields, &$otherContentModels)
	{
		// Get all of the non-translatable fields
		$nonTranslatableFields = array();

		foreach ($fieldLayout->getFields() as $fieldLayoutField)
		{
			$field = $fieldLayoutField->getField();

			if ($field && !$field->translatable)
			{
				$fieldType = $field->getFieldType();

				if ($fieldType && $fieldType->defineContentAttribute())
				{
					$nonTranslatableFields[$field->id] = $field;
				}
			}
		}

		if ($nonTranslatableFields)
		{
			// Get the other locales' content
			$rows = craft()->db->createCommand()
				->from($this->contentTable)
				->where(
					array('and', 'elementId = :elementId', 'locale != :locale'),
					array(':elementId' => $element->id, ':locale' => $content->locale))
				->queryAll();

			// Remove the column prefixes
			foreach ($rows as $i => $row)
			{
				$rows[$i] = $this->_removeColumnPrefixesFromRow($row);
			}

			$otherContentModels = ContentModel::populateModels($rows);

			foreach ($otherContentModels as $otherContentModel)
			{
				foreach ($nonTranslatableFields as $field)
				{
					$handle = $field->handle;
					$otherContentModel->$handle = $content->$handle;
				}

				$this->_saveContentRow($otherContentModel);
			}
		}
	}

	/**
	 * Updates the search indexes based on the new content values.
	 *
	 * @access private
	 * @param BaseElementModel $element
	 * @param ContentModel     $content
	 * @param FieldLayoutModel $fieldLayout
	 * @param array|null       &$nonTranslatableFields
	 * @param array|null       &$otherContentModels
	 */
	private function _updateSearchIndexes(BaseElementModel $element, ContentModel $content, FieldLayoutModel $fieldLayout, &$nonTranslatableFields = null, &$otherContentModels = null)
	{
		$searchKeywordsByLocale = array();

		foreach ($fieldLayout->getFields() as $fieldLayoutField)
		{
			$field = $fieldLayoutField->getField();

			if ($field)
			{
				$fieldType = $field->getFieldType();

				if ($fieldType)
				{
					$fieldType->element = $element;

					$handle = $field->handle;

					// Set the keywords for the content's locale
					$fieldSearchKeywords = $fieldType->getSearchKeywords($element->$handle);
					$searchKeywordsByLocale[$content->locale][$field->id] = $fieldSearchKeywords;

					// Should we queue up the other locales' new keywords too?
					if (isset($nonTranslatableFields[$field->id]))
					{
						foreach ($otherContentModels as $otherContentModel)
						{
							$searchKeywordsByLocale[$otherContentModel->locale][$field->id] = $fieldSearchKeywords;
						}
					}
				}
			}
		}

		foreach ($searchKeywordsByLocale as $localeId => $keywords)
		{
			craft()->search->indexElementFields($element->id, $localeId, $keywords);
		}
	}

	/**
	 * Removes the column prefixes from a given row.
	 *
	 * @access private
	 * @param array $row
	 * @return array
	 */
	private function _removeColumnPrefixesFromRow($row)
	{
		$fieldColumnPrefixLength = strlen($this->fieldColumnPrefix);

		foreach ($row as $column => $value)
		{
			if (strncmp($column, $this->fieldColumnPrefix, $fieldColumnPrefixLength) === 0)
			{
				$fieldHandle = substr($column, $fieldColumnPrefixLength);
				$row[$fieldHandle] = $value;
				unset($row[$column]);
			}
		}

		return $row;
	}
}
