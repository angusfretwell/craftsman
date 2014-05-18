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
class CategoriesController extends BaseController
{
	/**
	 * Category groups index.
	 */
	public function actionGroupIndex()
	{
		craft()->userSession->requireAdmin();

		$groups = craft()->categories->getAllGroups();

		$this->renderTemplate('settings/categories/index', array(
			'categoryGroups' => $groups
		));
	}

	/**
	 * Edit a category group.
	 *
	 * @param array $variables
	 */
	public function actionEditCategoryGroup(array $variables = array())
	{
		craft()->userSession->requireAdmin();

		// Breadcrumbs
		$variables['crumbs'] = array(
			array('label' => Craft::t('Settings'), 'url' => UrlHelper::getUrl('settings')),
			array('label' => Craft::t('Categories'),  'url' => UrlHelper::getUrl('settings/categories'))
		);

		$variables['brandNewGroup'] = false;

		if (!empty($variables['groupId']))
		{
			if (empty($variables['categoryGroup']))
			{
				$variables['categoryGroup'] = craft()->categories->getGroupById($variables['groupId']);

				if (!$variables['categoryGroup'])
				{
					throw new HttpException(404);
				}
			}

			$variables['title'] = $variables['categoryGroup']->name;
		}
		else
		{
			if (empty($variables['categoryGroup']))
			{
				$variables['categoryGroup'] = new CategoryGroupModel();
				$variables['brandNewGroup'] = true;
			}

			$variables['title'] = Craft::t('Create a new category group');
		}

		$variables['tabs'] = array(
			'settings'    => array('label' => Craft::t('Settings'), 'url' => '#categorygroup-settings'),
			'fieldLayout' => array('label' => Craft::t('Field Layout'), 'url' => '#categorygroup-fieldlayout')
		);

		$this->renderTemplate('settings/categories/_edit', $variables);
	}

	/**
	 * Save a category group.
	 */
	public function actionSaveGroup()
	{
		$this->requirePostRequest();
		craft()->userSession->requireAdmin();

		$group = new CategoryGroupModel();

		// Set the simple stuff
		$group->id        = craft()->request->getPost('groupId');
		$group->name      = craft()->request->getPost('name');
		$group->handle    = craft()->request->getPost('handle');
		$group->hasUrls   = craft()->request->getPost('hasUrls');
		$group->template  = craft()->request->getPost('template');
		$group->maxLevels = craft()->request->getPost('maxLevels');

		// Locale-spceific URL formats
		$locales = array();

		foreach (craft()->i18n->getSiteLocaleIds() as $localeId)
		{
			$locales[$localeId] = new SectionLocaleModel(array(
				'locale'          => $localeId,
				'urlFormat'       => craft()->request->getPost('urlFormat.'.$localeId),
				'nestedUrlFormat' => craft()->request->getPost('nestedUrlFormat.'.$localeId),
			));
		}

		$group->setLocales($locales);

		// Group the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost(false);
		$fieldLayout->type = ElementType::Category;
		$group->setFieldLayout($fieldLayout);

		// Save it
		if (craft()->categories->saveGroup($group))
		{
			craft()->userSession->setNotice(Craft::t('Category group saved.'));
			$this->redirectToPostedUrl($group);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save the category group.'));
		}

		// Send the category group back to the template
		craft()->urlManager->setRouteVariables(array(
			'categoryGroup' => $group
		));
	}

	/**
	 * Deletes a category group.
	 */
	public function actionDeleteCategoryGroup()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();
		craft()->userSession->requireAdmin();

		$groupId = craft()->request->getRequiredPost('id');

		craft()->categories->deleteGroupById($groupId);
		$this->returnJson(array('success' => true));
	}

	/**
	 * Saves a category.
	 */
	public function actionCreateCategory()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$groupId = craft()->request->getRequiredPost('groupId');

		craft()->userSession->requirePermission('editCategories:'.$groupId);

		$category = new CategoryModel();
		$category->groupId = $groupId;

		$category->getContent()->title = craft()->request->getPost('title');

		if (craft()->categories->saveCategory($category))
		{
			$this->returnJson(array(
				'success' => true,
				'id'      => $category->id,
				'title'   => $category->title,
				'status'  => $category->getStatus(),
				'url'     => $category->getUrl(),
			));
		}
		else
		{
			$this->returnJson(array(
				'success' => false
			));
		}
	}

	/**
	 * Deletes a category.
	 */
	public function actionDeleteCategory()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$categoryId = craft()->request->getRequiredPost('categoryId');
		$category = craft()->categories->getCategoryById($categoryId);

		if (!$category)
		{
			throw new Exception(Craft::t('No category exists with the ID “{id}”', array('id' => $categoryId)));
		}

		craft()->userSession->requirePermission('editCategories:'.$category->groupId);

		$success = craft()->categories->deleteCategoryById($categoryId);
		$this->returnJson(array('success' => $success));
	}
}
