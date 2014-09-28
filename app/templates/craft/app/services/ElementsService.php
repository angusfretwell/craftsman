<?php
namespace Craft;

/**
 * ElementsService provides APIs for managing elements.
 *
 * An instance of ElementsService is globally accessible in Craft via {@link WebApp::elements `craft()->elements`}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class ElementsService extends BaseApplicationComponent
{
	// Public Methods
	// =========================================================================

	// Finding Elements
	// -------------------------------------------------------------------------

	/**
	 * Returns an element criteria model for a given element type.
	 *
	 * This should be the starting point any time you want to fetch elements in Craft.
	 *
	 * ```php
	 * $criteria = craft()->elements->getCriteria(ElementType::Entry);
	 * $criteria->section = 'news';
	 * $entries = $criteria->find();
	 * ```
	 *
	 * @param string $type       The element type class handle (e.g. one of the values in the {@link ElementType} enum).
	 * @param mixed  $attributes Any criteria attribute values that should be pre-populated on the criteria model.
	 *
	 * @throws Exception
	 * @return ElementCriteriaModel An element criteria model, wired to fetch elements of the given $type.
	 */
	public function getCriteria($type, $attributes = null)
	{
		$elementType = $this->getElementType($type);

		if (!$elementType)
		{
			throw new Exception(Craft::t('No element type exists by the type “{type}”.', array('type' => $type)));
		}

		return new ElementCriteriaModel($attributes, $elementType);
	}

	/**
	 * Returns an element by its ID.
	 *
	 * If no element type is provided, the method will first have to run a DB query to determine what type of element
	 * the $elementId is, so you should definitely pass it if it’s known.
	 *
	 * The element’s status will not be a factor when usisng this method.
	 *
	 * @param int    $elementId   The element’s ID.
	 * @param null   $elementType The element type’s class handle.
	 * @param string $localeId    The locale to fetch the element in.
	 *                            Defaults to {@link WebApp::language `craft()->language`}.
	 *
	 * @return BaseElementModel|null The matching element, or `null`.
	 */
	public function getElementById($elementId, $elementType = null, $localeId = null)
	{
		if (!$elementId)
		{
			return null;
		}

		if (!$elementType)
		{
			$elementType = $this->getElementTypeById($elementId);

			if (!$elementType)
			{
				return null;
			}
		}

		$criteria = $this->getCriteria($elementType);
		$criteria->id = $elementId;
		$criteria->locale = $localeId;
		$criteria->status = null;
		$criteria->localeEnabled = null;
		return $criteria->first();
	}

	/**
	 * Returns an element by its URI.
	 *
	 * @param string      $uri         The element’s URI.
	 * @param string|null $localeId    The locale to look for the URI in, and to return the element in.
	 *                                 Defaults to {@link WebApp::language `craft()->language`}.
	 * @param bool        $enabledOnly Whether to only look for an enabled element. Defaults to `false`.
	 *
	 * @return BaseElementModel|null The matching element, or `null`.
	 */
	public function getElementByUri($uri, $localeId = null, $enabledOnly = false)
	{
		if ($uri === '')
		{
			$uri = '__home__';
		}

		if (!$localeId)
		{
			$localeId = craft()->language;
		}

		// First get the element ID and type

		$conditions = array('and',
			'elements_i18n.uri = :uri',
			'elements_i18n.locale = :locale'
		);

		$params = array(
			':uri'    => $uri,
			':locale' => $localeId
		);

		if ($enabledOnly)
		{
			$conditions[] = 'elements_i18n.enabled = 1';
			$conditions[] = 'elements.enabled = 1';
			$conditions[] = 'elements.archived = 0';
		}

		$result = craft()->db->createCommand()
			->select('elements.id, elements.type')
			->from('elements elements')
			->join('elements_i18n elements_i18n', 'elements_i18n.elementId = elements.id')
			->where($conditions, $params)
			->queryRow();

		if ($result)
		{
			// Return the actual element
			return $this->getElementById($result['id'], $result['type'], $localeId);
		}
	}

	/**
	 * Returns the element type(s) used by the element of a given ID(s).
	 *
	 * If a single ID is passed in (an int), then a single element type will be returned (a string), or `null` if
	 * no element exists by that ID.
	 *
	 * If an array is passed in, then an array will be returned.
	 *
	 * @param int|array $elementId An element’s ID, or an array of elements’ IDs.
	 *
	 * @return string|array|null The element type(s).
	 */
	public function getElementTypeById($elementId)
	{
		if (is_array($elementId))
		{
			return craft()->db->createCommand()
				->selectDistinct('type')
				->from('elements')
				->where(array('in', 'id', $elementId))
				->queryColumn();
		}
		else
		{
			return craft()->db->createCommand()
				->select('type')
				->from('elements')
				->where(array('id' => $elementId))
				->queryScalar();
		}
	}

	/**
	 * Finds elements.
	 *
	 * @param ElementCriteriaModel $criteria An element criteria model that defines the praameters for the elemetns
	 *                                       we should be looking for.
	 * @param bool                 $justIds  Whether the method should only return an array of the IDs of the matched
	 *                                       elements. Defaults to `false`.
	 *
	 * @return array The matched elements, or their IDs, depending on $justIds.
	 */
	public function findElements($criteria = null, $justIds = false)
	{
		$elements = array();
		$query = $this->buildElementsQuery($criteria, $contentTable, $fieldColumns);

		if ($query)
		{
			if ($justIds)
			{
				$query->select('elements.id');
			}

			if ($criteria->fixedOrder)
			{
				$ids = ArrayHelper::stringToArray($criteria->id);

				if (!$ids)
				{
					return array();
				}

				$query->order(craft()->db->getSchema()->orderByColumnValues('elements.id', $ids));
			}
			else if ($criteria->order && $criteria->order != 'score')
			{
				$order = $criteria->order;

				if (is_array($fieldColumns))
				{
					// Add the field column prefixes
					foreach ($fieldColumns as $column)
					{
						// Avoid matching fields named "asc" or "desc" in the string "column_name asc" or
						// "column_name desc"
						$order = preg_replace('/(?<!\s)\b'.$column['handle'].'\b/', $column['column'].'$1', $order);
					}
				}

				$query->order($order);
			}

			if ($criteria->offset)
			{
				$query->offset($criteria->offset);
			}

			if ($criteria->limit)
			{
				$query->limit($criteria->limit);
			}

			$results = $query->queryAll();

			if ($results)
			{
				if ($justIds)
				{
					foreach ($results as $result)
					{
						$elements[] = $result['id'];
					}
				}
				else
				{
					$elementType = $criteria->getElementType();
					$indexBy = $criteria->indexBy;
					$lastElement = null;

					foreach ($results as $result)
					{
						// Make a copy to pass to the onPopulateElement event
						$originalResult = array_merge($result);

						if ($contentTable)
						{
							// Separate the content values from the main element attributes
							$content = array(
								'id'        => (isset($result['contentId']) ? $result['contentId'] : null),
								'elementId' => $result['id'],
								'locale'    => $criteria->locale,
								'title'     => (isset($result['title']) ? $result['title'] : null)
							);

							unset($result['title']);

							if ($fieldColumns)
							{
								foreach ($fieldColumns as $column)
								{
									// Account for results where multiple fields have the same handle, but from
									// different columns e.g. two Matrix block types that each have a field with the
									// same handle

									$colName = $column['column'];
									$fieldHandle = $column['handle'];

									if (!isset($content[$fieldHandle]) || (empty($content[$fieldHandle]) && !empty($result[$colName])))
									{
										$content[$fieldHandle] = $result[$colName];
									}

									unset($result[$colName]);
								}
							}
						}

						$result['locale'] = $criteria->locale;
						$element = $elementType->populateElementModel($result);

						if ($contentTable)
						{
							$element->setContent($content);
						}

						if ($indexBy)
						{
							$elements[$element->$indexBy] = $element;
						}
						else
						{
							$elements[] = $element;
						}

						if ($lastElement)
						{
							$lastElement->setNext($element);
							$element->setPrev($lastElement);
						}
						else
						{
							$element->setPrev(false);
						}

						$lastElement = $element;

						// Fire an 'onPopulateElement' event
						$this->onPopulateElement(new Event($this, array(
							'element' => $element,
							'result'  => $originalResult
						)));
					}

					$lastElement->setNext(false);
				}
			}
		}

		return $elements;
	}

	/**
	 * Returns the total number of elements that match a given criteria.
	 *
	 * @param ElementCriteriaModel $criteria An element criteria model that defines the praameters for the elemetns
	 *                                       we should be counting.
	 *
	 * @return int The total number of elements that match the criteria.
	 */
	public function getTotalElements($criteria = null)
	{
		$query = $this->buildElementsQuery($criteria);

		if ($query)
		{
			$elementIds = $this->_getElementIdsFromQuery($query);

			if ($criteria->search)
			{
				$elementIds = craft()->search->filterElementIdsByQuery($elementIds, $criteria->search, false);
			}

			return count($elementIds);
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Preps a {@link DbCommand} object for querying for elements, based on a given element criteria.
	 *
	 * @param ElementCriteriaModel &$criteria     The element criteria model
	 * @param string               &$contentTable The content table that should be joined in. (This variable will
	 *                                            actually get defined by buildElementsQuery(), and is passed by
	 *                                            reference so whatever’s calling the method will have access to its
	 *                                            value.)
	 * @param array                &$fieldColumns Info about the content field columns being selected. (This variable
	 *                                            will actually get defined by buildElementsQuery(), and is passed by
	 *                                            reference so whatever’s calling the method will have access to its
	 *                                            value.)
	 *
	 * @return DbCommand|false The DbCommand object, or `false` if the method was able to determine ahead of time that
	 *                         there’s no chance any elements are going to be found with the given parameters.
	 */
	public function buildElementsQuery(&$criteria = null, &$contentTable = null, &$fieldColumns = null)
	{
		if (!($criteria instanceof ElementCriteriaModel))
		{
			$criteria = $this->getCriteria('Entry', $criteria);
		}

		$elementType = $criteria->getElementType();

		if (!$elementType->isLocalized())
		{
			// The criteria *must* be set to the primary locale
			$criteria->locale = craft()->i18n->getPrimarySiteLocaleId();
		}
		else if (!$criteria->locale)
		{
			// Default to the current app locale
			$criteria->locale = craft()->language;
		}

		// Set up the query
		// ---------------------------------------------------------------------

		$query = craft()->db->createCommand()
			->select('elements.id, elements.type, elements.enabled, elements.archived, elements.dateCreated, elements.dateUpdated, elements_i18n.slug, elements_i18n.uri, elements_i18n.enabled AS localeEnabled')
			->from('elements elements')
			->join('elements_i18n elements_i18n', 'elements_i18n.elementId = elements.id')
			->where('elements_i18n.locale = :locale', array(':locale' => $criteria->locale))
			->group('elements.id');

		if ($elementType->hasContent())
		{
			$contentTable = $elementType->getContentTableForElementsQuery($criteria);

			if ($contentTable)
			{
				$contentCols = 'content.id AS contentId';

				if ($elementType->hasTitles())
				{
					$contentCols .= ', content.title';
				}

				$fieldColumns = $elementType->getContentFieldColumnsForElementsQuery($criteria);

				foreach ($fieldColumns as $column)
				{
					$contentCols .= ', content.'.$column['column'];
				}

				$query->addSelect($contentCols);
				$query->join($contentTable.' content', 'content.elementId = elements.id');
				$query->andWhere('content.locale = :locale');
			}
		}

		// Basic element params
		// ---------------------------------------------------------------------

		// If the 'id' parameter is set to any empty value besides `null`, don't return anything
		if ($criteria->id !== null && empty($criteria->id))
		{
			return false;
		}

		if ($criteria->id)
		{
			$query->andWhere(DbHelper::parseParam('elements.id', $criteria->id, $query->params));
		}

		if ($criteria->archived)
		{
			$query->andWhere('elements.archived = 1');
		}
		else
		{
			$query->andWhere('elements.archived = 0');

			if ($criteria->status)
			{
				$statusConditions = array();
				$statuses = ArrayHelper::stringToArray($criteria->status);

				foreach ($statuses as $status)
				{
					$status = StringHelper::toLowerCase($status);

					// Is this a supported status?
					if (in_array($status, array_keys($elementType->getStatuses())))
					{
						if ($status == BaseElementModel::ENABLED)
						{
							$statusConditions[] = 'elements.enabled = 1';
						}
						else if ($status == BaseElementModel::DISABLED)
						{
							$statusConditions[] = 'elements.enabled = 0';
						}
						else
						{
							$elementStatusCondition = $elementType->getElementQueryStatusCondition($query, $status);

							if ($elementStatusCondition)
							{
								$statusConditions[] = $elementStatusCondition;
							}
							else if ($elementStatusCondition === false)
							{
								return false;
							}
						}
					}
				}

				if ($statusConditions)
				{
					if (count($statusConditions) == 1)
					{
						$statusCondition = $statusConditions[0];
					}
					else
					{
						array_unshift($statusConditions, 'or');
						$statusCondition = $statusConditions;
					}

					$query->andWhere($statusCondition);
				}
			}
		}

		if ($criteria->dateCreated)
		{
			$query->andWhere(DbHelper::parseDateParam('elements.dateCreated', $criteria->dateCreated, $query->params));
		}

		if ($criteria->dateUpdated)
		{
			$query->andWhere(DbHelper::parseDateParam('elements.dateUpdated', $criteria->dateUpdated, $query->params));
		}

		if ($elementType->hasTitles() && $criteria->title)
		{
			$query->andWhere(DbHelper::parseParam('content.title', $criteria->title, $query->params));
		}

		// i18n params
		// ---------------------------------------------------------------------

		if ($criteria->slug)
		{
			$query->andWhere(DbHelper::parseParam('elements_i18n.slug', $criteria->slug, $query->params));
		}

		if ($criteria->uri)
		{
			$query->andWhere(DbHelper::parseParam('elements_i18n.uri', $criteria->uri, $query->params));
		}

		if ($criteria->localeEnabled)
		{
			$query->andWhere('elements_i18n.enabled = 1');
		}

		// Relational params
		// ---------------------------------------------------------------------

		// Convert the old childOf and parentOf params to the relatedTo param
		// childOf(element)  => relatedTo({ source: element })
		// parentOf(element) => relatedTo({ target: element })
		if (!$criteria->relatedTo && ($criteria->childOf || $criteria->parentOf))
		{
			$relatedTo = array('and');

			if ($criteria->childOf)
			{
				$relatedTo[] = array('sourceElement' => $criteria->childOf, 'field' => $criteria->childField);
			}

			if ($criteria->parentOf)
			{
				$relatedTo[] = array('targetElement' => $criteria->parentOf, 'field' => $criteria->parentField);
			}

			$criteria->relatedTo = $relatedTo;
		}

		if ($criteria->relatedTo)
		{
			$relationParamParser = new ElementRelationParamParser();
			$relConditions = $relationParamParser->parseRelationParam($criteria->relatedTo, $query);

			if ($relConditions === false)
			{
				return false;
			}

			$query->andWhere($relConditions);

			// If there's only one relation criteria and it's specifically for grabbing target elements, allow the query
			// to order by the relation sort order
			if ($relationParamParser->isRelationFieldQuery())
			{
				$query->addSelect('sources1.sortOrder');
			}
		}

		// Give field types a chance to make changes
		// ---------------------------------------------------------------------

		foreach ($criteria->getSupportedFieldHandles() as $fieldHandle)
		{
			$field = craft()->fields->getFieldByHandle($fieldHandle);
			$fieldType = $field->getFieldType();

			if ($fieldType)
			{
				if ($fieldType->modifyElementsQuery($query, $criteria->$fieldHandle) === false)
				{
					return false;
				}
			}
		}

		// Give the element type a chance to make changes
		// ---------------------------------------------------------------------

		if ($elementType->modifyElementsQuery($query, $criteria) === false)
		{
			return false;
		}

		// Structure params
		// ---------------------------------------------------------------------

		if ($query->isJoined('structureelements'))
		{
			$query->addSelect('structureelements.root, structureelements.lft, structureelements.rgt, structureelements.level');

			if ($criteria->ancestorOf)
			{
				if (!$criteria->ancestorOf instanceof BaseElementModel)
				{
					$criteria->ancestorOf = craft()->elements->getElementById($criteria->ancestorOf, $elementType->getClassHandle());
				}

				if ($criteria->ancestorOf)
				{
					$query->andWhere(
						array('and',
							'structureelements.lft < :ancestorOf_lft',
							'structureelements.rgt > :ancestorOf_rgt',
							'structureelements.root = :ancestorOf_root'
						),
						array(
							':ancestorOf_lft'  => $criteria->ancestorOf->lft,
							':ancestorOf_rgt'  => $criteria->ancestorOf->rgt,
							':ancestorOf_root' => $criteria->ancestorOf->root
						)
					);

					if ($criteria->ancestorDist)
					{
						$query->andWhere('structureelements.level >= :ancestorOf_level',
							array(':ancestorOf_level' => $criteria->ancestorOf->level - $criteria->ancestorDist)
						);
					}
				}
			}

			if ($criteria->descendantOf)
			{
				if (!$criteria->descendantOf instanceof BaseElementModel)
				{
					$criteria->descendantOf = craft()->elements->getElementById($criteria->descendantOf, $elementType->getClassHandle());
				}

				if ($criteria->descendantOf)
				{
					$query->andWhere(
						array('and',
							'structureelements.lft > :descendantOf_lft',
							'structureelements.rgt < :descendantOf_rgt',
							'structureelements.root = :descendantOf_root'
						),
						array(
							':descendantOf_lft'  => $criteria->descendantOf->lft,
							':descendantOf_rgt'  => $criteria->descendantOf->rgt,
							':descendantOf_root' => $criteria->descendantOf->root
						)
					);

					if ($criteria->descendantDist)
					{
						$query->andWhere('structureelements.level <= :descendantOf_level',
							array(':descendantOf_level' => $criteria->descendantOf->level + $criteria->descendantDist)
						);
					}
				}
			}

			if ($criteria->siblingOf)
			{
				if (!$criteria->siblingOf instanceof BaseElementModel)
				{
					$criteria->siblingOf = craft()->elements->getElementById($criteria->siblingOf, $elementType->getClassHandle());
				}

				if ($criteria->siblingOf)
				{
					$query->andWhere(
						array('and',
							'structureelements.level = :siblingOf_level',
							'structureelements.root = :siblingOf_root',
							'structureelements.elementId != :siblingOf_elementId'
						),
						array(
							':siblingOf_level'     => $criteria->siblingOf->level,
							':siblingOf_root'      => $criteria->siblingOf->root,
							':siblingOf_elementId' => $criteria->siblingOf->id
						)
					);

					if ($criteria->siblingOf->level != 1)
					{
						$parent = $criteria->siblingOf->getParent();

						if ($parent)
						{
							$query->andWhere(
								array('and',
									'structureelements.lft > :siblingOf_lft',
									'structureelements.rgt < :siblingOf_rgt'
								),
								array(
									':siblingOf_lft'  => $parent->lft,
									':siblingOf_rgt'  => $parent->rgt
								)
							);
						}
						else
						{
							return false;
						}
					}
				}
			}

			if ($criteria->prevSiblingOf)
			{
				if (!$criteria->prevSiblingOf instanceof BaseElementModel)
				{
					$criteria->prevSiblingOf = craft()->elements->getElementById($criteria->prevSiblingOf, $elementType->getClassHandle());
				}

				if ($criteria->prevSiblingOf)
				{
					$query->andWhere(
						array('and',
							'structureelements.level = :prevSiblingOf_level',
							'structureelements.rgt = :prevSiblingOf_rgt',
							'structureelements.root = :prevSiblingOf_root'
						),
						array(
							':prevSiblingOf_level' => $criteria->prevSiblingOf->level,
							':prevSiblingOf_rgt'   => $criteria->prevSiblingOf->lft - 1,
							':prevSiblingOf_root'  => $criteria->prevSiblingOf->root
						)
					);
				}
			}

			if ($criteria->nextSiblingOf)
			{
				if (!$criteria->nextSiblingOf instanceof BaseElementModel)
				{
					$criteria->nextSiblingOf = craft()->elements->getElementById($criteria->nextSiblingOf, $elementType->getClassHandle());
				}

				if ($criteria->nextSiblingOf)
				{
					$query->andWhere(
						array('and',
							'structureelements.level = :nextSiblingOf_level',
							'structureelements.lft = :nextSiblingOf_lft',
							'structureelements.root = :nextSiblingOf_root'
						),
						array(
							':nextSiblingOf_level' => $criteria->nextSiblingOf->level,
							':nextSiblingOf_lft'   => $criteria->nextSiblingOf->rgt + 1,
							':nextSiblingOf_root'  => $criteria->nextSiblingOf->root
						)
					);
				}
			}

			if ($criteria->positionedBefore)
			{
				if (!$criteria->positionedBefore instanceof BaseElementModel)
				{
					$criteria->positionedBefore = craft()->elements->getElementById($criteria->positionedBefore, $elementType->getClassHandle());
				}

				if ($criteria->positionedBefore)
				{
					$query->andWhere(
						array('and',
							'structureelements.rgt < :positionedBefore_rgt',
							'structureelements.root = :positionedBefore_root'
						),
						array(
							':positionedBefore_rgt'   => $criteria->positionedBefore->lft,
							':positionedBefore_root'  => $criteria->positionedBefore->root
						)
					);
				}
			}

			if ($criteria->positionedAfter)
			{
				if (!$criteria->positionedAfter instanceof BaseElementModel)
				{
					$criteria->positionedAfter = craft()->elements->getElementById($criteria->positionedAfter, $elementType->getClassHandle());
				}

				if ($criteria->positionedAfter)
				{
					$query->andWhere(
						array('and',
							'structureelements.lft > :positionedAfter_lft',
							'structureelements.root = :positionedAfter_root'
						),
						array(
							':positionedAfter_lft'   => $criteria->positionedAfter->rgt,
							':positionedAfter_root'  => $criteria->positionedAfter->root
						)
					);
				}
			}

			if ($criteria->level || $criteria->depth)
			{
				// TODO: 'depth' is deprecated; use 'level' instead.
				$level = ($criteria->level ? $criteria->level : $criteria->depth);
				$query->andWhere(DbHelper::parseParam('structureelements.level', $level, $query->params));
			}
		}

		// Search
		// ---------------------------------------------------------------------

		if ($criteria->search)
		{
			$elementIds = $this->_getElementIdsFromQuery($query);
			$scoredSearchResults = ($criteria->order == 'score');
			$filteredElementIds = craft()->search->filterElementIdsByQuery($elementIds, $criteria->search, $scoredSearchResults);

			// No results?
			if (!$filteredElementIds)
			{
				return array();
			}

			$query->andWhere(array('in', 'elements.id', $filteredElementIds));

			if ($scoredSearchResults)
			{
				// Order the elements in the exact order that SearchService returned them in
				$query->order(craft()->db->getSchema()->orderByColumnValues('elements.id', $filteredElementIds));
			}
		}

		return $query;
	}

	/**
	 * Returns an element’s URI for a given locale.
	 *
	 * @param int    $elementId The element’s ID.
	 * @param string $localeId  The locale to search for the element’s URI in.
	 *
	 * @return string|null The element’s URI, or `null`.
	 */
	public function getElementUriForLocale($elementId, $localeId)
	{
		return craft()->db->createCommand()
			->select('uri')
			->from('elements_i18n')
			->where(array('elementId' => $elementId, 'locale' => $localeId))
			->queryScalar();
	}

	/**
	 * Returns the locales that a given element is enabled in.
	 *
	 * @param int $elementId The element’s ID.
	 *
	 * @return array The locales that the element is enabled in. If the element could not be found, an empty array
	 *               will be returned.
	 */
	public function getEnabledLocalesForElement($elementId)
	{
		return craft()->db->createCommand()
			->select('locale')
			->from('elements_i18n')
			->where(array('elementId' => $elementId, 'enabled' => 1))
			->queryColumn();
	}

	// Saving Elements
	// -------------------------------------------------------------------------

	/**
	 * Handles all of the routine tasks that go along with saving elements.
	 *
	 * Those tasks include:
	 *
	 * - Validating its content (if $validateContent is `true`, or it’s left as `null` and the element is enabled)
	 * - Ensuring the element has a title if its type {@link BaseElementType::hasTitles() has titles}, and giving it a
	 *   default title in the event that $validateContent is set to `false`
	 * - Saving a row in the `elements` table
	 * - Assigning the element’s ID on the element model, if it’s a new element
	 * - Assigning the element’s ID on the element’s content model, if there is one and it’s a new set of content
	 * - Updating the search index with new keywords from the element’s content
	 * - Setting a unique URI on the element, if it’s supposed to have one.
	 * - Saving the element’s row(s) in the `elements_i18n` and `content` tables
	 * - Deleting any rows in the `elements_i18n` and `content` tables that no longer need to be there
	 * - Calling the field types’ {@link BaseFieldType::onAfterElementSave() onAfterElementSave()} methods
	 * - Cleaing any template caches that the element was involved in
	 *
	 * This method should be called by a service’s “saveX()” method, _after_ it is done validating any attributes on
	 * the element that are of particular concern to its element type. For example, if the element were an entry,
	 * saveElement() should be called only after the entry’s sectionId and typeId attributes had been validated to
	 * ensure that they point to valid section and entry type IDs.
	 *
	 * @param BaseElementModel $element         The element that is being saved
	 * @param bool|null        $validateContent Whether the element's content should be validated. If left 'null', it
	 *                                          will depend on whether the element is enabled or not.
	 *
	 * @throws Exception|\Exception
	 * @return bool
	 */
	public function saveElement(BaseElementModel $element, $validateContent = null)
	{
		$elementType = $this->getElementType($element->getElementType());

		$isNewElement = !$element->id;

		// Validate the content first
		if ($elementType->hasContent())
		{
			if ($validateContent === null)
			{
				$validateContent = (bool) $element->enabled;
			}

			if ($validateContent && !craft()->content->validateContent($element))
			{
				$element->addErrors($element->getContent()->getErrors());
				return false;
			}
			else
			{
				// Make sure there's a title
				if ($elementType->hasTitles())
				{
					$fields = array('title');
					$content = $element->getContent();
					$content->setRequiredFields($fields);

					if (!$content->validate($fields) && $content->hasErrors('title'))
					{
						// Just set *something* on it
						if ($isNewElement)
						{
							$content->title = 'New '.$element->getClassHandle();
						}
						else
						{
							$content->title = $element->getClassHandle().' '.$element->id;
						}
					}
				}
			}
		}

		// Get the element record
		if (!$isNewElement)
		{
			$elementRecord = ElementRecord::model()->findByAttributes(array(
				'id'   => $element->id,
				'type' => $element->getElementType()
			));

			if (!$elementRecord)
			{
				throw new Exception(Craft::t('No element exists with the ID “{id}”', array('id' => $element->id)));
			}
		}
		else
		{
			$elementRecord = new ElementRecord();
			$elementRecord->type = $element->getElementType();
		}

		// Set the attributes
		$elementRecord->enabled = (bool) $element->enabled;
		$elementRecord->archived = (bool) $element->archived;

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Save the element record first
			$success = $elementRecord->save(false);

			if ($success)
			{
				if ($isNewElement)
				{
					// Save the element id on the element model, in case {id} is in the URL format
					$element->id = $elementRecord->id;

					if ($elementType->hasContent())
					{
						$element->getContent()->elementId = $element->id;
					}
				}

				// Save the content
				if ($elementType->hasContent())
				{
					craft()->content->saveContent($element, false, (bool) $element->id);
				}

				// Update the search index
				craft()->search->indexElementAttributes($element);

				// Update the locale records and content

				// We're saving all of the element's locales here to ensure that they all exist and to update the URI in
				// the event that the URL format includes some value that just changed

				$localeRecords = array();

				if (!$isNewElement)
				{
					$existingLocaleRecords = ElementLocaleRecord::model()->findAllByAttributes(array(
						'elementId' => $element->id
					));

					foreach ($existingLocaleRecords as $record)
					{
						$localeRecords[$record->locale] = $record;
					}
				}

				$mainLocaleId = $element->locale;

				$locales = $element->getLocales();
				$localeIds = array();

				if (!$locales)
				{
					throw new Exception('All elements must have at least one locale associated with them.');
				}

				foreach ($locales as $localeId => $localeInfo)
				{
					if (is_numeric($localeId) && is_string($localeInfo))
					{
						$localeId = $localeInfo;
						$localeInfo = array();
					}

					$localeIds[] = $localeId;

					if (!isset($localeInfo['enabledByDefault']))
					{
						$localeInfo['enabledByDefault'] = true;
					}

					if (isset($localeRecords[$localeId]))
					{
						$localeRecord = $localeRecords[$localeId];
					}
					else
					{
						$localeRecord = new ElementLocaleRecord();

						$localeRecord->elementId = $element->id;
						$localeRecord->locale    = $localeId;
						$localeRecord->enabled   = $localeInfo['enabledByDefault'];
					}

					// Is this the main locale?
					$isMainLocale = ($localeId == $mainLocaleId);

					if ($isMainLocale)
					{
						$localizedElement = $element;
					}
					else
					{
						// Copy the element for this locale
						$localizedElement = $element->copy();
						$localizedElement->locale = $localeId;

						if ($localeRecord->id)
						{
							// Keep the original slug
							$localizedElement->slug = $localeRecord->slug;
						}
						else
						{
							// Default to the main locale's slug
							$localizedElement->slug = $element->slug;
						}
					}

					if ($elementType->hasContent())
					{
						if (!$isMainLocale)
						{
							$content = null;

							if (!$isNewElement)
							{
								// Do we already have a content row for this locale?
								$content = craft()->content->getContent($localizedElement);
							}

							if (!$content)
							{
								$content = craft()->content->createContent($localizedElement);
								$content->setAttributes($element->getContent()->getAttributes());
								$content->id = null;
								$content->locale = $localeId;
							}

							$localizedElement->setContent($content);
						}

						if (!$localizedElement->getContent()->id)
						{
							craft()->content->saveContent($localizedElement, false, false);
						}
					}

					// Set a valid/unique slug and URI
					ElementHelper::setValidSlug($localizedElement);
					ElementHelper::setUniqueUri($localizedElement);

					$localeRecord->slug = $localizedElement->slug;
					$localeRecord->uri  = $localizedElement->uri;

					if ($isMainLocale)
					{
						$localeRecord->enabled = (bool) $element->localeEnabled;
					}

					$success = $localeRecord->save();

					if (!$success)
					{
						// Pass any validation errors on to the element
						$element->addErrors($localeRecord->getErrors());

						// Don't bother with any of the other locales
						break;
					}
				}

				if ($success)
				{
					if (!$isNewElement)
					{
						// Delete the rows that don't need to be there anymore

						craft()->db->createCommand()->delete('elements_i18n', array('and',
							'elementId = :elementId',
							array('not in', 'locale', $localeIds)
						), array(
							':elementId' => $element->id
						));

						if ($elementType->hasContent())
						{
							craft()->db->createCommand()->delete($element->getContentTable(), array('and',
								'elementId = :elementId',
								array('not in', 'locale', $localeIds)
							), array(
								':elementId' => $element->id
							));
						}
					}

					// Call the field types' onAfterElementSave() methods
					$fieldLayout = $element->getFieldLayout();

					if ($fieldLayout)
					{
						foreach ($fieldLayout->getFields() as $fieldLayoutField)
						{
							$field = $fieldLayoutField->getField();

							if ($field)
							{
								$fieldType = $field->getFieldType();

								if ($fieldType)
								{
									$fieldType->element = $element;
									$fieldType->onAfterElementSave();
								}
							}
						}
					}

					// Finally, delete any caches involving this element. (Even do this for new elements, since they
					// might pop up in a cached criteria.)
					craft()->templateCache->deleteCachesByElement($element);
				}
			}

			if ($transaction !== null)
			{
				if ($success)
				{
					$transaction->commit();
				}
				else
				{
					$transaction->rollback();
				}
			}

			if (!$success && $isNewElement)
			{
				$element->id = null;

				if ($elementType->hasContent())
				{
					$element->getContent()->id = null;
					$element->getContent()->elementId = null;
				}
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		return $success;
	}

	/**
	 * Updates an element’s slug and URI, along with any descendants.
	 *
	 * @param BaseElementModel $element            The element to update.
	 * @param bool             $updateOtherLocales Whether the element’s other locales should also be updated.
	 * @param bool             $updateDescendants  Whether the element’s descendants should also be updated.
	 *
	 * @return null
	 */
	public function updateElementSlugAndUri(BaseElementModel $element, $updateOtherLocales = true, $updateDescendants = true)
	{
		ElementHelper::setUniqueUri($element);

		craft()->db->createCommand()->update('elements_i18n', array(
			'slug' => $element->slug,
			'uri'  => $element->uri
		), array(
			'elementId' => $element->id,
			'locale'    => $element->locale
		));

		// Delete any caches involving this element
		craft()->templateCache->deleteCachesByElement($element);

		if ($updateOtherLocales)
		{
			$this->updateElementSlugAndUriInOtherLocales($element);
		}

		if ($updateDescendants)
		{
			$this->updateDescendantSlugsAndUris($element);
		}
	}

	/**
	 * Updates an element’s slug and URI, for any locales besides the given one.
	 *
	 * @param BaseElementModel $element The element to update.
	 *
	 * @return null
	 */
	public function updateElementSlugAndUriInOtherLocales(BaseElementModel $element)
	{
		foreach (craft()->i18n->getSiteLocaleIds() as $localeId)
		{
			if ($localeId == $element->locale)
			{
				continue;
			}

			$elementInOtherLocale = $this->getElementById($element->id, $element->getElementType(), $localeId);

			if ($elementInOtherLocale)
			{
				$this->updateElementSlugAndUri($elementInOtherLocale, false, false);
			}
		}
	}

	/**
	 * Updates an element’s descendants’ slugs and URIs.
	 *
	 * @param BaseElementModel $element The element whose descendants should be updated.
	 *
	 * @return null
	 */
	public function updateDescendantSlugsAndUris(BaseElementModel $element)
	{
		$criteria = $this->getCriteria($element->getElementType());
		$criteria->descendantOf = $element;
		$criteria->descendantDist = 1;
		$criteria->status = null;
		$criteria->localeEnabled = null;
		$children = $criteria->find();

		foreach ($children as $child)
		{
			$this->updateElementSlugAndUri($child);
		}
	}

	/**
	 * Merges two elements together.
	 *
	 * This method will upddate the following:
	 *
	 * - Any relations involving the merged elmeent
	 * - Any structures that contain the merged element
	 * - Any reference tags in textual custom fields referencing the merged element
	 *
	 * @param int $mergedElementId     The ID of the element that is going away.
	 * @param int $prevailingElementId The ID of the element that is sticking around.
	 *
	 * @throws \Exception
	 * @return bool Whether the elements were merged successfully.
	 */
	public function mergeElementsByIds($mergedElementId, $prevailingElementId)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Update any relations that point to the merged element
			$relations = craft()->db->createCommand()
				->select('id, fieldId, sourceId, sourceLocale')
				->from('relations')
				->where(array('targetId' => $mergedElementId))
				->queryAll();

			foreach ($relations as $relation)
			{
				// Make sure the persisting element isn't already selected in the same field
				$persistingElementIsRelatedToo = (bool) craft()->db->createCommand()
					->from('relations')
					->where(array(
						'fieldId'      => $relation['fieldId'],
						'sourceId'     => $relation['sourceId'],
						'sourceLocale' => $relation['sourceLocale'],
						'targetId'     => $prevailingElementId
					))
					->count('id');

				if (!$persistingElementIsRelatedToo)
				{
					craft()->db->createCommand()->update('relations', array(
						'targetId' => $prevailingElementId
					), array(
						'id' => $relation['id']
					));
				}
			}

			// Update any structures that the merged element is in
			$structureElements = craft()->db->createCommand()
				->select('id, structureId')
				->from('structureelements')
				->where(array('elementId' => $mergedElementId))
				->queryAll();

			foreach ($structureElements as $structureElement)
			{
				// Make sure the persisting element isn't already a part of that structure
				$persistingElementIsInStructureToo = (bool) craft()->db->createCommand()
					->from('structureElements')
					->where(array(
						'structureId' => $structureElement['structureId'],
						'elementId' => $prevailingElementId
					))
					->count('id');

				if (!$persistingElementIsInStructureToo)
				{
					craft()->db->createCommand()->update('relations', array(
						'elementId' => $prevailingElementId
					), array(
						'id' => $structureElement['id']
					));
				}
			}

			// Update any reference tags
			$elementType = $this->getElementTypeById($prevailingElementId);

			if ($elementType)
			{
				$refTagPrefix = '{'.lcfirst($elementType).':';

				craft()->tasks->createTask('FindAndReplace', Craft::t('Updating element references'), array(
					'find'    => $refTagPrefix.$mergedElementId.':',
					'replace' => $refTagPrefix.$prevailingElementId.':',
				));

				craft()->tasks->createTask('FindAndReplace', Craft::t('Updating element references'), array(
					'find'    => $refTagPrefix.$mergedElementId.'}',
					'replace' => $refTagPrefix.$prevailingElementId.'}',
				));
			}

			// Fire an 'onMergeElements' event
			$this->onMergeElements(new Event($this, array(
				'mergedElementId'     => $mergedElementId,
				'prevailingElementId' => $prevailingElementId
			)));

			// Now delete the merged element
			$success = $this->deleteElementById($mergedElementId);

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return $success;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	/**
	 * Deletes an element(s) by its ID(s).
	 *
	 * @param int|array $elementIds The element’s ID, or an array of elements’ IDs.
	 *
	 * @throws \Exception
	 * @return bool Whether the element(s) were deleted successfully.
	 */
	public function deleteElementById($elementIds)
	{
		if (!$elementIds)
		{
			return false;
		}

		if (!is_array($elementIds))
		{
			$elementIds = array($elementIds);
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// First delete any structure nodes with these elements, so NestedSetBehavior can do its thing. We need to
			// go one-by-one in case one of theme deletes the record of another in the process.
			foreach ($elementIds as $elementId)
			{
				$records = StructureElementRecord::model()->findAllByAttributes(array(
					'elementId' => $elementId
				));

				foreach ($records as $record)
				{
					$record->deleteNode();
				}
			}

			// Delete the caches before they drop their elementId relations (passing `false` because there's no chance
			// this element is suddenly going to show up in a new query)
			craft()->templateCache->deleteCachesByElementId($elementIds, false);

			// Fire an 'onBeforeDeleteElements' event
			$this->onBeforeDeleteElements(new Event($this, array(
				'elementIds' => $elementIds
			)));

			// Now delete the rows in the elements table
			if (count($elementIds) == 1)
			{
				$condition = array('id' => $elementIds[0]);
				$matrixBlockCondition = array('ownerId' => $elementIds[0]);
			}
			else
			{
				$condition = array('in', 'id', $elementIds);
				$matrixBlockCondition = array('in', 'ownerId', $elementIds);
			}

			// First delete any Matrix blocks that belong to this element(s)
			$matrixBlockIds = craft()->db->createCommand()
				->select('id')
				->from('matrixblocks')
				->where($matrixBlockCondition)
				->queryColumn();

			if ($matrixBlockIds)
			{
				craft()->matrix->deleteBlockById($matrixBlockIds);
			}

			$affectedRows = craft()->db->createCommand()->delete('elements', $condition);

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return (bool) $affectedRows;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	/**
	 * Deletes elements by a given type.
	 *
	 * @param string $type The element type class handle.
	 *
	 * @return bool Whether the elements were deleted successfully.
	 */
	public function deleteElementsByType($type)
	{
		// Get the IDs and let deleteElementById() take care of the actual deletion
		$elementIds = craft()->db->createCommand()
			->select('id')
			->from('elements')
			->where('type = :type', array(':type' => $type))
			->queryColumn();

		if ($elementIds)
		{
			$this->deleteElementById($elementIds);

			// Delete the template caches
			craft()->templateCache->deleteCachesByElementType($type);
		}
	}

	// Element types
	// -------------------------------------------------------------------------

	/**
	 * Returns all installed element types.
	 *
	 * @return BaseElementType[] The installed element types.
	 */
	public function getAllElementTypes()
	{
		return craft()->components->getComponentsByType(ComponentType::Element);
	}

	/**
	 * Returns an element type by its class handle.
	 *
	 * @param string $class The element type class handle.
	 *
	 * @return BaseElementType|null The element type, or `null`.
	 */
	public function getElementType($class)
	{
		return craft()->components->getComponentByTypeAndClass(ComponentType::Element, $class);
	}

	// Misc
	// -------------------------------------------------------------------------

	/**
	 * Parses a string for element [reference tags](http://buildwithcraft.com/docs/reference-tags).
	 *
	 * @param string $str The string to parse.
	 *
	 * @return string The parsed string.
	 */
	public function parseRefs($str)
	{
		if (strpos($str, '{') !== false)
		{
			global $refTagsByElementType;
			$refTagsByElementType = array();

			$str = preg_replace_callback('/\{(\w+)\:([^\:\}]+)(?:\:([^\:\}]+))?\}/', function($matches)
			{
				global $refTagsByElementType;

				$elementTypeHandle = ucfirst($matches[1]);
				$token = '{'.StringHelper::randomString(9).'}';

				$refTagsByElementType[$elementTypeHandle][] = array('token' => $token, 'matches' => $matches);

				return $token;
			}, $str);

			if ($refTagsByElementType)
			{
				$search = array();
				$replace = array();

				$things = array('id', 'ref');

				foreach ($refTagsByElementType as $elementTypeHandle => $refTags)
				{
					$elementType = craft()->elements->getElementType($elementTypeHandle);

					if (!$elementType)
					{
						// Just put the ref tags back the way they were
						foreach ($refTags as $refTag)
						{
							$search[] = $refTag['token'];
							$replace[] = $refTag['matches'][0];
						}
					}
					else
					{
						$refTagsById = array();
						$refTagsByRef = array();

						foreach ($refTags as $refTag)
						{
							// Searching by ID?
							if (is_numeric($refTag['matches'][2]))
							{
								$refTagsById[$refTag['matches'][2]][] = $refTag;
							}
							else
							{
								$refTagsByRef[$refTag['matches'][2]][] = $refTag;
							}
						}

						// Things are about to get silly...
						foreach ($things as $thing)
						{
							$refTagsByThing = ${'refTagsBy'.ucfirst($thing)};

							if ($refTagsByThing)
							{
								$criteria = craft()->elements->getCriteria($elementTypeHandle);
								$criteria->$thing = array_keys($refTagsByThing);
								$elements = $criteria->find();

								$elementsByThing = array();

								foreach ($elements as $element)
								{
									$elementsByThing[$element->$thing] = $element;
								}

								foreach ($refTagsByThing as $thingVal => $refTags)
								{
									if (isset($elementsByThing[$thingVal]))
									{
										$element = $elementsByThing[$thingVal];
									}
									else
									{
										$element = false;
									}

									foreach($refTags as $refTag)
									{
										$search[] = $refTag['token'];

										if ($element)
										{
											if (!empty($refTag['matches'][3]) && isset($element->{$refTag['matches'][3]}))
											{
												$value = (string) $element->{$refTag['matches'][3]};
												$replace[] = $this->parseRefs($value);
											}
											else
											{
												// Default to the URL
												$replace[] = $element->getUrl();
											}
										}
										else
										{
											$replace[] = $refTag['matches'][0];
										}
									}
								}
							}
						}
					}
				}

				$str = str_replace($search, $replace, $str);
			}

			unset ($refTagsByElementType);
		}

		return $str;
	}

	/**
	 * Fires an 'onPopulateElement' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onPopulateElement(Event $event)
	{
		$this->raiseEvent('onPopulateElement', $event);
	}

	/**
	 * Fires an 'onMergeElements' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onMergeElements(Event $event)
	{
		$this->raiseEvent('onMergeElements', $event);
	}

	/**
	 * Fires an 'onBeforeDeleteElements' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeDeleteElements(Event $event)
	{
		$this->raiseEvent('onBeforeDeleteElements', $event);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the unique element IDs that match a given element query.
	 *
	 * @param DbCommand $query
	 *
	 * @return array
	 */
	private function _getElementIdsFromQuery(DbCommand $query)
	{
		// Get the matched element IDs, and then have the SearchService filter them.
		$elementIdsQuery = craft()->db->createCommand()
			->select('elements.id')
			->from('elements elements')
			->group('elements.id');

		$elementIdsQuery->setWhere($query->getWhere());
		$elementIdsQuery->setJoin($query->getJoin());

		$elementIdsQuery->params = $query->params;
		return $elementIdsQuery->queryColumn();
	}
}
