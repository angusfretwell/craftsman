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
 * Handles asset transform tasks
 */
class AssetTransformsController extends BaseController
{
	/**
	 * Shows the asset transform list.
	 */
	public function actionTransformIndex()
	{
		craft()->userSession->requireAdmin();

		$variables['transforms'] = craft()->assetTransforms->getAllTransforms();
		$variables['transformModes'] = AssetTransformModel::getTransformModes();

		$this->renderTemplate('settings/assets/transforms/index', $variables);
	}

	/**
	 * Edit an asset transform.
	 *
	 * @param array $variables
	 * @throws HttpException
	 */
	public function actionEditTransform(array $variables = array())
	{
		craft()->userSession->requireAdmin();

		if (empty($variables['transform']))
		{
			if (!empty($variables['handle']))
			{
				$variables['transform'] = craft()->assetTransforms->getTransformByHandle($variables['handle']);
				if (!$variables['transform'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['transform'] = new AssetTransformModel();
			}
		}

		$this->renderTemplate('settings/assets/transforms/_settings', $variables);
	}

	/**
	 * Saves an asset source.
	 */
	public function actionSaveTransform()
	{
		$this->requirePostRequest();

		$transform = new AssetTransformModel();
		$transform->id = craft()->request->getPost('transformId');
		$transform->name = craft()->request->getPost('name');
		$transform->handle = craft()->request->getPost('handle');
		$transform->width = craft()->request->getPost('width');
		$transform->height = craft()->request->getPost('height');
		$transform->mode = craft()->request->getPost('mode');
		$transform->position = craft()->request->getPost('position');
		$transform->quality = craft()->request->getPost('quality');

		$errors = false;
		if (empty($transform->width) && empty($transform->height))
		{
			craft()->userSession->setError(Craft::t('You must set at least one of the dimensions.'));
			$errors = true;
		}
		if (!empty($transform->quality) && (!is_numeric($transform->quality) || $transform->quality > 100 || $transform->quality < 1))
		{
			craft()->userSession->setError(Craft::t('Quality must be a number between 1 and 100 (included).'));
			$errors = true;
		}

		if (!$errors)
		{
			// Did it save?
			if (craft()->assetTransforms->saveTransform($transform))
			{
				craft()->userSession->setNotice(Craft::t('Transform saved.'));
				$this->redirectToPostedUrl($transform);
			}
			else
			{
				craft()->userSession->setError(Craft::t('Couldn’t save source.'));
			}
		}

		// Send the transform back to the template
		craft()->urlManager->setRouteVariables(array(
			'transform' => $transform
		));
	}

	/**
	 * Deletes an asset transform.
	 */
	public function actionDeleteTransform()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$transformId = craft()->request->getRequiredPost('id');

		craft()->assetTransforms->deleteTransform($transformId);

		$this->returnJson(array('success' => true));
	}
}
