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
 * Field layout behavior
 */
class FieldLayoutBehavior extends BaseBehavior
{
	private $_fieldLayout;
	private $_elementType;

	/**
	 * Constructor
	 */
	public function __construct($elementType)
	{
		$this->_elementType = $elementType;
	}

	/**
	 * Returns the owner's field layout.
	 *
	 * @return FieldLayoutModel
	 */
	public function getFieldLayout()
	{
		if (!isset($this->_fieldLayout))
		{
			if (!empty($this->getOwner()->fieldLayoutId))
			{
				$this->_fieldLayout = craft()->fields->getLayoutById($this->getOwner()->fieldLayoutId);
			}

			if (empty($this->_fieldLayout))
			{
				$this->_fieldLayout = new FieldLayoutModel();
				$this->_fieldLayout->type = $this->_elementType;
			}
		}

		return $this->_fieldLayout;
	}

	/**
	 * Sets the owner's field layout.
	 *
	 * @param FieldLayoutModel $fieldLayout
	 */
	public function setFieldLayout(FieldLayoutModel $fieldLayout)
	{
		$this->_fieldLayout = $fieldLayout;
	}
}
