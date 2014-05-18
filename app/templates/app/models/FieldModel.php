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
 * Field model class
 */
class FieldModel extends BaseComponentModel
{
	private $_fieldType;

	/**
	 * Use the translated field name as the string representation.
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
		return array_merge(parent::defineAttributes(), array(
			'groupId'      => AttributeType::Number,
			'name'         => AttributeType::String,
			'handle'       => AttributeType::String,
			'context'      => AttributeType::String,
			'instructions' => AttributeType::String,
			'required'     => AttributeType::Bool,
			'translatable' => AttributeType::Bool,

			'oldHandle'    => AttributeType::String,
		));
	}

	/**
	 * Returns the field type this field is using.
	 *
	 * @return BaseFieldType|null
	 */
	public function getFieldType()
	{
		if (!isset($this->_fieldType))
		{
			$this->_fieldType = craft()->fields->populateFieldType($this);

			// Might not actually exist
			if (!$this->_fieldType)
			{
				$this->_fieldType = false;
			}
		}

		// Return 'null' instead of 'false' if it doesn't exist
		if ($this->_fieldType)
		{
			return $this->_fieldType;
		}
	}

	/**
	 * Returns the field's group.
	 *
	 * @return EntryUserModel
	 */
	public function getGroup()
	{
		return craft()->fields->getGroupById($this->groupId);
	}
}
