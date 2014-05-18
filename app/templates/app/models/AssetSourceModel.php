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
class AssetSourceModel extends BaseComponentModel
{
	private $_sourceType;

	/**
	 * Use the translated source name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return Craft::t($this->name);
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		$attributes = parent::defineAttributes();

		$attributes['name'] = AttributeType::String;
		$attributes['type']['default'] = 'Local';
		$attributes['sortOrder'] = AttributeType::String;
		$attributes['fieldLayoutId'] = AttributeType::Number;

		return $attributes;
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'fieldLayout' => new FieldLayoutBehavior(ElementType::Entry),
		);
	}

	/**
	 * Returns the source type this source is using.
	 *
	 * @return BaseAssetSourceType|null
	 */
	public function getSourceType()
	{
		if (!isset($this->_sourceType))
		{
			$this->_sourceType = craft()->assetSources->populateSourceType($this);

			// Might not actually exist
			if (!$this->_sourceType)
			{
				$this->_sourceType = false;
			}
		}

		// Return 'null' instead of 'false' if it doesn't exist
		if ($this->_sourceType)
		{
			return $this->_sourceType;
		}
	}
}
