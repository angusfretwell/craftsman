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
 * Matrix block model class
 */
class MatrixSettingsModel extends BaseModel
{
	private $_matrixField;
	private $_blockTypes;

	/**
	 * Constructor
	 *
	 * @param FieldModel|null $matrixField
	 */
	function __construct(FieldModel $matrixField = null)
	{
		$this->_matrixField = $matrixField;
	}

	/**
	 * Defines this model's attributes.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'maxBlocks' => AttributeType::Number,
		);
	}

	/**
	 * Returns the field associated with this.
	 *
	 * @return FieldModel
	 */
	public function getField()
	{
		return $this->_matrixField;
	}

	/**
	 * Returns the block types.
	 *
	 * @return array
	 */
	public function getBlockTypes()
	{
		if (!isset($this->_blockTypes))
		{
			if (!empty($this->_matrixField->id))
			{
				$this->_blockTypes = craft()->matrix->getBlockTypesByFieldId($this->_matrixField->id);
			}
			else
			{
				$this->_blockTypes = array();
			}
		}

		return $this->_blockTypes;
	}

	/**
	 * Sets the block types.
	 *
	 * @param array $blockTypes
	 */
	public function setBlockTypes($blockTypes)
	{
		$this->_blockTypes = $blockTypes;
	}

	/**
	 * Validates the block type settings.
	 *
	 * @param array|null $attributes
	 * @param bool $clearErrors
	 * @return bool
	 */
	public function validate($attributes = null, $clearErrors = true)
	{
		// Enforce $clearErrors without copying code if we don't have to
		$validates = parent::validate($attributes, $clearErrors);

		if (!craft()->matrix->validateFieldSettings($this))
		{
			$validates = false;
		}

		return $validates;
	}
}
