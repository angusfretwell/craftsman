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
 * CP functions
 */
class CpVariable
{
	/**
	 * Get the sections of the CP.
	 *
	 * @return array
	 */
	public function nav()
	{
		$nav['dashboard'] = array('name' => Craft::t('Dashboard'));

		if (craft()->sections->getTotalEditableSections())
		{
			$nav['entries'] = array('name' => Craft::t('Entries'));
		}

		$globals = craft()->globals->getEditableSets();

		if ($globals)
		{
			$nav['globals'] = array('name' => Craft::t('Globals'), 'url' => 'globals/'.$globals[0]->handle);
		}

		if (craft()->categories->getEditableGroupIds())
		{
			$nav['categories'] = array('name' => Craft::t('Categories'));
		}

		if (craft()->assetSources->getTotalViewableSources())
		{
			$nav['assets'] = array('name' => Craft::t('Assets'));
		}

		if (craft()->getEdition() == Craft::Pro && craft()->userSession->checkPermission('editUsers'))
		{
			$nav['users'] = array('name' => Craft::t('Users'));
		}

		// Add any Plugin nav items
		$plugins = craft()->plugins->getPlugins();

		foreach ($plugins as $plugin)
		{
			if ($plugin->hasCpSection())
			{
				if (craft()->userSession->checkPermission('accessPlugin-'.$plugin->getClassHandle()))
				{
					$lcHandle = StringHelper::toLowerCase($plugin->getClassHandle());
					$nav[$lcHandle] = array('name' => $plugin->getName());
				}
			}
		}

		$firstSegment = craft()->request->getSegment(1);

		if ($firstSegment == 'myaccount')
		{
			$firstSegment = 'users';
		}

		foreach ($nav as $handle => &$item)
		{
			$item['sel'] = ($handle == $firstSegment);

			if (isset($item['url']))
			{
				$item['url'] = UrlHelper::getUrl($item['url']);
			}
			else
			{
				$item['url'] = UrlHelper::getUrl($handle);
			}
		}

		return $nav;
	}

	/**
	 * Returns the list of settings.
	 *
	 * @return array
	 */
	public function settings()
	{
		$system = Craft::t('System');

		$settings[$system]['general'] = array('icon' => 'general', 'label' => Craft::t('General'));
		$settings[$system]['routes'] = array('icon' => 'routes', 'label' => Craft::t('Routes'));

		if (craft()->getEdition() == Craft::Pro)
		{
			$settings[$system]['users'] = array('icon' => 'users', 'label' => Craft::t('Users'));
		}

		$settings[$system]['email'] = array('icon' => 'mail', 'label' => Craft::t('Email'));
		$settings[$system]['plugins'] = array('icon' => 'plugin', 'label' => Craft::t('Plugins'));

		$content = Craft::t('Content');

		$settings[$content]['fields'] = array('icon' => 'field', 'label' => Craft::t('Fields'));
		$settings[$content]['sections'] = array('icon' => 'section', 'label' => Craft::t('Sections'));
		$settings[$content]['assets'] = array('icon' => 'assets', 'label' => Craft::t('Assets'));
		$settings[$content]['globals'] = array('icon' => 'globe', 'label' => Craft::t('Globals'));
		$settings[$content]['categories'] = array('icon' => 'categories', 'label' => Craft::t('Categories'));
		$settings[$content]['tags'] = array('icon' => 'tags', 'label' => Craft::t('Tags'));

		if (craft()->getEdition() == Craft::Pro)
		{
			$settings[$content]['locales'] = array('icon' => 'language', 'label' => Craft::t('Locales'));
		}

		return $settings;
	}

	/**
	 * Returns whether the CP alerts are cached.
	 *
	 * @return bool
	 */
	public function areAlertsCached()
	{
		// The license key status gets cached on each Elliott request
		return (craft()->et->getLicenseKeyStatus() !== false);
	}

	/**
	 * Returns an array of alerts to display in the CP.
	 *
	 * @return array
	 */
	public function getAlerts()
	{
		return CpHelper::getAlerts(craft()->request->getPath());
	}
}
