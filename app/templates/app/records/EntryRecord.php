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
class EntryRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'entries';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'postDate'   => AttributeType::DateTime,
			'expiryDate' => AttributeType::DateTime,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		$relations = array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'section' => array(static::BELONGS_TO, 'SectionRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'type'    => array(static::BELONGS_TO, 'EntryTypeRecord', 'onDelete' => static::CASCADE),
			'author'  => array(static::BELONGS_TO, 'UserRecord', 'onDelete' => static::CASCADE),
		);

		if (craft()->getEdition() == Craft::Pro)
		{
			$relations['versions'] = array(static::HAS_MANY, 'EntryVersionRecord', 'elementId');
		}

		return $relations;
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('sectionId')),
			array('columns' => array('typeId')),
			array('columns' => array('postDate')),
			array('columns' => array('expiryDate')),
		);
	}

	/**
	 * @return array
	 */
	public function scopes()
	{
		return array(
			'ordered' => array('order' => 'postDate'),
		);
	}
}
