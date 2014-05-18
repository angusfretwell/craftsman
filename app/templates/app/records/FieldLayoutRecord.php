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
 * Field layoutr record class
 */
class FieldLayoutRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'fieldlayouts';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'type' => array(AttributeType::ClassName, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'tabs'   => array(static::HAS_MANY, 'FieldLayoutTabRecord', 'layoutId', 'order' => 'tabs.sortOrder'),
			'fields' => array(static::HAS_MANY, 'FieldLayoutFieldRecord', 'layoutId', 'order' => 'fields.sortOrder'),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('type')),
		);
	}
}
