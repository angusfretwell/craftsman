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
class InfoModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'            => AttributeType::Number,
			'version'       => array(AttributeType::String, 'required' => true, 'default' => '0'),
			'build'         => array(AttributeType::Number, 'required' => true, 'default' => '0'),
			'schemaVersion' => array(AttributeType::String, 'required' => true, 'default' => '0'),
			'edition'       => array(AttributeType::Number, 'required' => true, 'default' => 0),
			'releaseDate'   => array(AttributeType::DateTime, 'required' => true),
			'siteName'      => array(AttributeType::Name, 'required' => true),
			'siteUrl'       => array(AttributeType::Url, 'required' => true),
			'timezone'      => array(AttributeType::String, 'maxLength' => 30, 'default' => date_default_timezone_get()),
			'on'            => AttributeType::Bool,
			'maintenance'   => AttributeType::Bool,
			'track'         => array(AttributeType::String, 'maxLength' => 40, 'column' => ColumnType::Varchar, 'required' => true),
			'uid'           => AttributeType::String,
		);
	}
}
