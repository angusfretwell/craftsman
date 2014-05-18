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
 * Resave Elements Task
 */
class ResaveElementsTask extends BaseTask
{
	private $_elementIds;

	/**
	 * Returns the default description for this task.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		$elementType = craft()->elements->getElementType($this->getSettings()->elementType);

		return Craft::t('Resaving {type}', array(
			'type' => StringHelper::toLowerCase($elementType->getName())
		));
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
			'elementType' => AttributeType::String,
			'criteria'    => AttributeType::Mixed,
		);
	}

	/**
	 * Gets the total number of steps for this task.
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$settings = $this->getSettings();

		// Let's save ourselves some trouble and just clear all the caches for this element type
		craft()->templateCache->deleteCachesByElementType($settings->elementType);

		// Now find the affected element IDs
		$criteria = craft()->elements->getCriteria($settings->elementType, $settings->criteria);
		$criteria->offset = null;
		$criteria->limit = null;
		$criteria->order = null;
		$this->_elementIds = $criteria->ids();
		return count($this->_elementIds);
	}

	/**
	 * Runs a task step.
	 *
	 * @param int $step
	 * @return bool
	 */
	public function runStep($step)
	{
		$element = craft()->elements->getElementById($this->_elementIds[$step]);

		if (craft()->elements->saveElement($element, false))
		{
			return true;
		}
		else
		{
			$error = 'Encountered the following validation errors when trying to save '.strtolower($element->getElementType()).' element "'.$element.'" with the ID "'.$element->id.'":';

			foreach ($element->getAllErrors() as $attributeError)
			{
				$error .= "\n - {$attributeError}";
			}

			return $error;
		}
	}
}
