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
class NumberFieldType extends BaseFieldType
{
	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Number');
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'min'      => array(AttributeType::Number, 'default' => 0),
			'max'      => array(AttributeType::Number, 'compare' => '>= min'),
			'decimals' => array(AttributeType::Number, 'default' => 0),
		);
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->render('_components/fieldtypes/Number/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		$attribute = ModelHelper::getNumberAttributeConfig($this->settings->min, $this->settings->max, $this->settings->decimals);
		$attribute['default'] = 0;
		return $attribute;
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		if ($this->isFresh() && ($value < $this->settings->min || $value > $this->settings->max))
		{
			$value = $this->settings->min;
		}

		return craft()->templates->render('_includes/forms/text', array(
			'name'  => $name,
			'value' => craft()->numberFormatter->formatDecimal($value, false),
			'size'  => 5
		));
	}

	/**
	 * Returns the input value as it should be saved to the database.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public function prepValueFromPost($data)
	{
		if ($data === '')
		{
			return 0;
		}
		else
		{
			return LocalizationHelper::normalizeNumber($data);
		}
	}
}
