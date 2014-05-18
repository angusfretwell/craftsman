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
class SectionLocaleRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'sections_i18n';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'locale'           => array(AttributeType::Locale, 'required' => true),
			'enabledByDefault' => array(AttributeType::Bool, 'default' => true),
			'urlFormat'        => AttributeType::UrlFormat,
			'nestedUrlFormat'  => AttributeType::UrlFormat,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'section' => array(static::BELONGS_TO, 'SectionRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'locale'  => array(static::BELONGS_TO, 'LocaleRecord', 'locale', 'required' => true, 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('sectionId', 'locale'), 'unique' => true),
		);
	}
}
