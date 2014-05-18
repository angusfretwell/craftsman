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
 * Base component model class
 *
 * Used for transporting component data throughout the system.
 *
 * @abstract
 */
abstract class BaseComponentModel extends BaseModel
{
	private $_settingErrors = array();

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'       => AttributeType::Number,
			'type'     => array(AttributeType::String),
			'settings' => AttributeType::Mixed,
		);
	}

	/**
	 * Returns whether this is a new component.
	 *
	 * @return bool
	 */
	public function isNew()
	{
		return (!$this->id || strncmp($this->id, 'new', 3) === 0);
	}

	/**
	 * Returns whether there are setting errors.
	 *
	 * @param string|null $attribute
	 * @return bool
	 */
	public function hasSettingErrors($attribute = null)
	{
		if ($attribute === null)
		{
			return $this->_settingErrors !== array();
		}
		else
		{
			return isset($this->_settingErrors[$attribute]);
		}
	}

	/**
	 * Returns the errors for all settings attributes.
	 *
	 * @param string|null $attribute
	 * @return array
	 */
	public function getSettingErrors($attribute = null)
	{
		if ($attribute === null)
		{
			return $this->_settingErrors;
		}
		else
		{
			return isset($this->_settingErrors[$attribute]) ? $this->_settingErrors[$attribute] : array();
		}
	}

	/**
	 * Adds a new error to the specified setting attribute.
	 *
	 * @param string $attribute
	 * @param string $error
	 */
	public function addSettingsError($attribute,$error)
	{
		$this->_settingErrors[$attribute][] = $error;
	}

	/**
	 * Adds a list of settings errors.
	 *
	 * @param array $errors
	 */
	public function addSettingErrors($errors)
	{
		foreach ($errors as $attribute => $error)
		{
			if (is_array($error))
			{
				foreach ($error as $e)
				{
					$this->addSettingsError($attribute, $e);
				}
			}
			else
			{
				$this->addSettingsError($attribute, $error);
			}
		}
	}
}
