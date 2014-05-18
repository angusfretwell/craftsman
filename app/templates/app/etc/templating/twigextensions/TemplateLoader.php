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
 * Loads Craft templates into Twig.
 */
class TemplateLoader implements \Twig_LoaderInterface, \Twig_ExistsLoaderInterface
{
	 /**
	 * Checks if a template exists.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function exists($name)
	{
		return craft()->templates->doesTemplateExist($name);
	}

	/**
	 * Gets the source code of a template.
	 *
	 * @param  string $name The name of the template to load, or a StringTemplate object.
	 * @throws Exception
	 * @return string       The template source code.
	 */
	public function getSource($name)
	{
		if (is_string($name))
		{
			$template = craft()->templates->findTemplate($name);

			if (IOHelper::isReadable($template))
			{
				return IOHelper::getFileContents($template);
			}
			else
			{
				throw new Exception(Craft::t('Tried to read the template at {path}, but could not. Check the permissions.', array('path' => $template)));
			}
		}
		else
		{
			return $name->template;
		}
	}

	/**
	 * Gets the cache key to use for the cache for a given template.
	 *
	 * @param string $name The name of the template to load, or a StringTemplate object.
	 * @return string      The cache key (the path to the template)
	 */
	public function getCacheKey($name)
	{
		if (is_string($name))
		{
			return craft()->templates->findTemplate($name);
		}
		else
		{
			return $name->cacheKey;
		}
	}

	/**
	 * Returns whether the cached template is still up-to-date with the latest template.
	 *
	 * @param string    $name The template name, or a StringTemplate object.
	 * @param timestamp $time The last modification time of the cached template
	 * @return bool
	 */
	public function isFresh($name, $time)
	{
		// If this is a CP request and a DB update is needed, force a recompile.
		if (craft()->request->isCpRequest() && craft()->updates->isCraftDbMigrationNeeded())
		{
			return false;
		}

		if (is_string($name))
		{
			$sourceModifiedTime = IOHelper::getLastTimeModified(craft()->templates->findTemplate($name));
			return $sourceModifiedTime->getTimestamp() <= $time;
		}
		else
		{
			return false;
		}
	}
}
