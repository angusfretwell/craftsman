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
 * Matrix block type model class
 */
class MatrixBlockTypeModel extends BaseModel
{
	public $hasFieldErrors = false;

	private $_fields;

	/**
	 * Use the block type handle as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->handle;
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'fieldLayout' => new FieldLayoutBehavior(ElementType::MatrixBlock),
		);
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'            => AttributeType::Number,
			'fieldId'       => AttributeType::Number,
			'fieldLayoutId' => AttributeType::String,
			'name'          => AttributeType::String,
			'handle'        => AttributeType::String,
			'sortOrder'     => AttributeType::Number,
		);
	}

	/**
	 * Returns whether this is a new component.
	 *
	 * @return bool
	 */
	public function isNew()
	{
		return (!$this->id || strncmp($this->id, 'new', 3) === 0);
	}

	/**
	 * Returns the fields associated with this block type.
	 *
	 * @return array
	 */
	public function getFields()
	{
		if (!isset($this->_fields))
		{
			$this->_fields = array();

			$fieldLayoutFields = $this->getFieldLayout()->getFields();

			foreach ($fieldLayoutFields as $fieldLayoutField)
			{
				$field = $fieldLayoutField->getField();
				$field->required = $fieldLayoutField->required;
				$this->_fields[] = $field;
			}
		}

		return $this->_fields;
	}

	/*
	 * Sets the fields associated with this block type.
	 *
	 * @param array $fields
	 */
	public function setFields($fields)
	{
		$this->_fields = $fields;
	}
}
