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
 * Field layout field model class
 */
class FieldLayoutFieldModel extends BaseModel
{
	private $_layout;

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'        => AttributeType::Number,
			'layoutId'  => AttributeType::Number,
			'tabId'     => AttributeType::Number,
			'fieldId'   => AttributeType::Name,
			'required'  => AttributeType::Bool,
			'sortOrder' => AttributeType::SortOrder,
		);
	}

	/**
	 * Returns the tab's layout.
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getLayout()
	{
		if (!isset($this->_layout))
		{
			if ($this->layoutId)
			{
				$this->_layout = craft()->fields->getLayoutById($this->layoutId);
			}
			else
			{
				$this->_layout = false;
			}
		}

		if ($this->_layout)
		{
			return $this->_layout;
		}
	}

	/**
	 * Sets the tab's layout.
	 *
	 * @param FieldLayoutModel $layout
	 */
	public function setLayout(FieldLayoutModel $layout)
	{
		$this->_layout = $layout;
	}

	/**
	 * Returns the actual field model.
	 *
	 * @return FieldModel|null
	 */
	public function getField()
	{
		if ($this->fieldId)
		{
			return craft()->fields->getFieldById($this->fieldId);
		}
	}
}
