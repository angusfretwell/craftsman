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
 * Asset source model class
 *
 * Used for transporting asset source data throughout the system.
 */
class AssetIndexDataModel extends BaseComponentModel
{
	/**
	 * Use the translated source name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->uri;
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'		=> AttributeType::Number,
			'sourceId'	=> AttributeType::Number,
			'sessionId' => AttributeType::String,
			'offset'	=> AttributeType::Number,
			'uri'     	=> AttributeType::String,
			'size' 		=> AttributeType::Number,
			'recordId'	=> AttributeType::Number
		);
	}
}
