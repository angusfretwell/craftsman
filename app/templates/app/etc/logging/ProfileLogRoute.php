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
class ProfileLogRoute extends \CProfileLogRoute
{
	/**
	 * @access protected
	 * @param $view
	 * @param $data
	 * @return mixed
	 */
	protected function render($view, $data)
	{
		$isAjax = craft()->request->isAjaxRequest();
		$mimeType = craft()->request->getMimeType();

		if (craft()->config->get('devMode') && !craft()->request->isResourceRequest())
		{
			if ($this->showInFireBug)
			{
				if ($isAjax && $this->ignoreAjaxInFireBug)
				{
					return;
				}

				$view .= '-firebug';
			}
			else if(!(craft() instanceof \CWebApplication) || $isAjax)
			{
				return;
			}

			if ($mimeType !== 'text/html')
			{
				return;
			}

			$viewFile = craft()->path->getCpTemplatesPath().'logging/'.$view.'.php';
			include(craft()->findLocalizedFile($viewFile, 'en'));
		}
	}
}
