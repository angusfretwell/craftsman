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
 * Field record class
 */
class FieldLayoutFieldRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'fieldlayoutfields';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'required'  => AttributeType::Bool,
			'sortOrder' => AttributeType::SortOrder,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'layout' => array(static::BELONGS_TO, 'FieldLayoutRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'tab'    => array(static::BELONGS_TO, 'FieldLayoutTabRecord', 'onDelete' => static::CASCADE),
			'field'  => array(static::BELONGS_TO, 'FieldRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('layoutId', 'fieldId'), 'unique' => true),
			array('columns' => array('sortOrder')),
		);
	}
}
