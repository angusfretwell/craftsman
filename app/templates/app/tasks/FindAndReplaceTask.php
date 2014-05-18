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
 * Find and Replace task
 */
class FindAndReplaceTask extends BaseTask
{
	private $_table;
	private $_textColumns;
	private $_matrixFieldIds;

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'find'          => AttributeType::String,
			'replace'       => AttributeType::String,
			'matrixFieldId' => AttributeType::String,
		);
	}

	/**
	 * Returns the default description for this task.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		$settings = $this->getSettings();

		return Craft::t('Replacing “{find}” with “{replace}”', array(
			'find'    => $settings->find,
			'replace' => $settings->replace
		));
	}

	/**
	 * Gets the total number of steps for this task.
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$this->_textColumns = array();
		$this->_matrixFieldIds = array();

		// Is this for a Matrix field?
		$matrixFieldId = $this->getSettings()->matrixFieldId;

		if ($matrixFieldId)
		{
			$matrixField = craft()->fields->getFieldById($matrixFieldId);

			if (!$matrixField || $matrixField->type != 'Matrix')
			{
				return 0;
			}

			$this->_table = craft()->matrix->getContentTableName($matrixField);

			$blockTypes = craft()->matrix->getBlockTypesByFieldId($matrixFieldId);

			foreach ($blockTypes as $blockType)
			{
				$fieldColumnPrefix = 'field_'.$blockType->handle.'_';

				foreach ($blockType->getFields() as $field)
				{
					$this->_checkField($field, $fieldColumnPrefix);
				}
			}
		}
		else
		{
			$this->_table = 'content';

			foreach (craft()->fields->getAllFields() as $field)
			{
				$this->_checkField($field, 'field_');
			}
		}

		return count($this->_textColumns) + count($this->_matrixFieldIds);
	}

	/**
	 * Runs a task step.
	 *
	 * @param int $step
	 * @return bool
	 */
	public function runStep($step)
	{
		$settings = $this->getSettings();

		if (isset($this->_textColumns[$step]))
		{
			craft()->db->createCommand()->replace($this->_table, $this->_textColumns[$step], $settings->find, $settings->replace);
			return true;
		}
		else
		{
			$step -= count($this->_textColumns);

			if (isset($this->_matrixFieldIds[$step]))
			{
				$field = craft()->fields->getFieldById($this->_matrixFieldIds[$step]);

				if ($field)
				{
					return $this->runSubTask('FindAndReplace', Craft::t('Working in Matrix field “{field}”', array('field' => $field->name)), array(
						'find'          => $settings->find,
						'replace'       => $settings->replace,
						'matrixFieldId' => $field->id
					));
				}
				else
				{
					// Oh what the hell.
					return true;
				}
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Checks whether the given field is saving data into a textual column, and saves it accordingly
	 *
	 * @access private
	 * @param FieldModel $field
	 * @param string     $fieldColumnPrefix
	 * @return bool
	 */
	private function _checkField(FieldModel $field, $fieldColumnPrefix)
	{
		if ($field->type == 'Matrix')
		{
			$this->_matrixFieldIds[] = $field->id;
		}
		else
		{
			$fieldType = $field->getFieldType();

			if ($fieldType)
			{
				$attributeConfig = $fieldType->defineContentAttribute();

				if ($attributeConfig && $attributeConfig != AttributeType::Number)
				{
					$attributeConfig = ModelHelper::normalizeAttributeConfig($attributeConfig);

					if ($attributeConfig['type'] == AttributeType::String)
					{
						$this->_textColumns[] = $fieldColumnPrefix.$field->handle;
					}
				}
			}
		}
	}
}
