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
 * Find and Replace tool
 */
class FindAndReplaceTool extends BaseTool
{
	/**
	 * Returns the tool name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Find and Replace');
	}

	/**
	 * Returns the tool's icon value.
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return 'wand';
	}

	/**
	 * Returns the tool's options HTML.
	 *
	 * @return string
	 */
	public function getOptionsHtml()
	{
		return craft()->templates->renderMacro('_includes/forms', 'textField', array(
			array(
				'name'        => 'find',
				'placeholder' => Craft::t('Find'),
			)
		)) .
		craft()->templates->renderMacro('_includes/forms', 'textField', array(
			array(
				'name'        => 'replace',
				'placeholder' => Craft::t('Replace'),
			)
		));
	}

	/**
	 * Performs the tool's action.
	 *
	 * @param array $params
	 * @return array
	 */
	public function performAction($params = array())
	{
		if (!empty($params['find']) && !empty($params['replace']))
		{
			craft()->tasks->createTask('FindAndReplace', null, array(
				'find'    => $params['find'],
				'replace' => $params['replace']
			));
		}
	}
}
