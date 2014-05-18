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
 * TODO: create save function which calls parent::save and then updates the meta data table (keywords, author, etc)
 */
class AssetFileRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assetfiles';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'filename'		=> array(AttributeType::String, 'required' => true),
			'kind'			=> array('column' => ColumnType::Varchar, 'maxLength' => 50, 'required' => true, 'default' => 'unknown'),
			'width'			=> array(AttributeType::Number, 'min' => 0, 'column' => ColumnType::SmallInt),
			'height'		=> array(AttributeType::Number, 'min' => 0, 'column' => ColumnType::SmallInt),
			'size'			=> array(AttributeType::Number, 'min' => 0, 'column' => ColumnType::Int),
			'dateModified'	=> AttributeType::DateTime
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'source'  => array(static::BELONGS_TO, 'AssetSourceRecord', 'required' => false, 'onDelete' => static::CASCADE),
			'folder'  => array(static::BELONGS_TO, 'AssetFolderRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('filename', 'folderId'), 'unique' => true),
		);
	}
}
