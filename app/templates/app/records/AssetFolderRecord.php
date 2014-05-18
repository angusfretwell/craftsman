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
class AssetFolderRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assetfolders';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'     => array(AttributeType::String, 'required' => true),
			'path'     => array(AttributeType::String),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'parent' => array(static::BELONGS_TO, 'AssetFolderRecord', 'onDelete' => static::CASCADE),
			'source' => array(static::BELONGS_TO, 'AssetSourceRecord', 'required' => false, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name', 'parentId', 'sourceId'), 'unique' => true),
		);
	}
}
