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
 * Stores Matrix block types
 */
class MatrixBlockTypeRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'matrixblocktypes';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'       => array(AttributeType::Name, 'required' => true),
			'handle'     => array(AttributeType::Handle, 'required' => true),
			'sortOrder'  => AttributeType::SortOrder,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'field'       => array(static::BELONGS_TO, 'FieldRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'fieldLayout' => array(static::BELONGS_TO, 'FieldLayoutRecord', 'onDelete' => static::SET_NULL),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name', 'fieldId'), 'unique' => true),
			array('columns' => array('handle', 'fieldId'), 'unique' => true),
		);
	}
}
