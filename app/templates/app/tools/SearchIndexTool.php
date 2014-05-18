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
 * Search Index tool
 */
class SearchIndexTool extends BaseTool
{
	/**
	 * Returns the tool name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Rebuild Search Indexes');
	}

	/**
	 * Returns the tool's icon value.
	 *
	 * @return string
	 */
	public function getIconValue()
	{
		return 'search';
	}

	/**
	 * Performs the tool's action.
	 *
	 * @param array $params
	 * @return array
	 */
	public function performAction($params = array())
	{
		if (!empty($params['start']))
		{
			// Get all the element IDs ever
			$elements = craft()->db->createCommand()
				->select('id, type')
				->from('elements')
				->queryAll();

			$batch = array();

			foreach ($elements as $element)
			{
				$batch[] = array('params' => $element);
			}

			return array(
				'batches' => array($batch)
			);
		}
		else
		{
			// Get the element type
			$elementType = craft()->elements->getElementType($params['type']);

			if ($elementType)
			{
				if ($elementType->isLocalized())
				{
					$localeIds = craft()->i18n->getSiteLocaleIds();
				}
				else
				{
					$localeIds = array(craft()->i18n->getPrimarySiteLocaleId());
				}

				$criteria = craft()->elements->getCriteria($params['type'], array(
					'id'            => $params['id'],
					'status'        => null,
					'localeEnabled' => null,
				));

				foreach ($localeIds as $localeId)
				{
					$criteria->locale = $localeId;
					$element = $criteria->first();

					if ($element)
					{
						craft()->search->indexElementAttributes($element);

						if ($elementType->hasContent())
						{
							$fieldLayout = $element->getFieldLayout();
							$content     = $element->getContent();

							$keywords = array();

							foreach ($fieldLayout->getFields() as $fieldLayoutField)
							{
								$field = $fieldLayoutField->getField();

								if ($field)
								{
									$fieldType = $field->getFieldType();

									if ($fieldType)
									{
										$fieldType->element = $element;

										$handle = $field->handle;

										// Set the keywords for the content's locale
										$fieldSearchKeywords = $fieldType->getSearchKeywords($element->$handle);
										$keywords[$field->id] = $fieldSearchKeywords;
									}
								}
							}

							craft()->search->indexElementFields($element->id, $localeId, $keywords);
						}
					}
				}
			}
		}
	}
}
