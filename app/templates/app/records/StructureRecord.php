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
class StructureRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'structures';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'maxLevels'      => array(AttributeType::Number, 'min' => 1, 'column' => ColumnType::SmallInt),
			'movePermission' => AttributeType::String,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'elements' => array(static::HAS_MANY, 'StructureElementRecord', 'structureId'),
		);
	}
}
