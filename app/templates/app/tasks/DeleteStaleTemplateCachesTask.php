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
 * Delete Stale Template Caches Task
 */
class DeleteStaleTemplateCachesTask extends BaseTask
{
	private $_criteria;
	private $_elementIds;
	private $_deletedCacheIds;

	/**
	 * Returns the default description for this task.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('Deleting stale template caches');
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
			'elementId' => AttributeType::Mixed,
		);
	}

	/**
	 * Gets the total number of steps for this task.
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		$this->_deletedCacheIds = array();

		$elementId = $this->getSettings()->elementId;

		// What type of element(s) are we dealing with?
		$elementType = craft()->elements->getElementTypeById($elementId);

		if (!$elementType)
		{
			return 0;
		}

		$query = craft()->db->createCommand()
			->select('*')
			->from('templatecachecriteria');

		if (is_array($elementType))
		{
			$query->where(array('in', 'type', $elementType));
		}
		else
		{
			$query->where('type = :type', array(':type' => $elementType));
		}

		if (is_array($elementId))
		{
			$this->_elementIds = $elementId;
		}
		else
		{
			$this->_elementIds = array($elementId);
		}

		$this->_criteria = $query->queryAll();

		return count($this->_criteria);
	}

	/**
	 * Runs a task step.
	 *
	 * @param int $step
	 * @return bool
	 */
	public function runStep($step)
	{
		$row = $this->_criteria[$step];

		if (!in_array($row['cacheId'], $this->_deletedCacheIds))
		{
			$params = JsonHelper::decode($row['criteria']);
			$criteria = craft()->elements->getCriteria($row['type'], $params);
			$criteriaElementIds = $criteria->ids();

			foreach ($this->_elementIds as $elementId)
			{
				if (in_array($elementId, $criteriaElementIds))
				{
					craft()->templateCache->deleteCacheById($row['cacheId']);
					$this->_deletedCacheIds[] = $row['cacheId'];
					break;
				}
			}
		}

		return true;
	}
}
