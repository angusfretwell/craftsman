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
class FeedsVariable
{
	/**
	 * @param      $url
	 * @param int  $limit
	 * @param int  $offset
	 * @param null $cacheDuration
	 * @return array
	 */
	public function getFeedItems($url, $limit = 0, $offset = 0, $cacheDuration = null)
	{
		$limit = NumberHelper::makeNumeric($limit);
		$offset = NumberHelper::makeNumeric($offset);
		$items = craft()->feeds->getFeedItems($url, $limit, $offset, $cacheDuration);

		// Prevent everyone from having to use the |raw filter when outputting the title and content
		$rawProperties = array('title', 'content', 'summary');

		foreach ($items as &$item)
		{
			foreach ($rawProperties as $prop)
			{
				$item[$prop] = TemplateHelper::getRaw($item[$prop]);
			}
		}

		return $items;
	}
}
