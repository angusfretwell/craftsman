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
 * Handles asset tasks
 */
class AssetsController extends BaseController
{
	protected $allowAnonymous = array('actionGenerateTransform');

	/**
	 * Upload a file
	 */
	public function actionUploadFile()
	{
		$this->requireAjaxRequest();
		$folderId = craft()->request->getPost('folderId');

		// Conflict resolution data
		$userResponse = craft()->request->getPost('userResponse');
		$responseInfo = craft()->request->getPost('additionalInfo');
		$fileName = craft()->request->getPost('fileName');

		// For a conflict resolution, the folder ID is no longer there and no file is actually being uploaded
		if (!empty($folderId) && empty($userResponse))
		{
			try
			{
				craft()->assets->checkPermissionByFolderIds($folderId, 'uploadToAssetSource');
			}
			catch (Exception $e)
			{
				$this->returnErrorJson($e->getMessage());
			}
		}

		$response = craft()->assets->uploadFile($folderId, $userResponse, $responseInfo, $fileName);

		$this->returnJson($response->getResponseData());
	}

	/**
	 * Uploads a file directly to a field for an entry.
	 *
	 * @throws Exception
	 */
	public function actionExpressUpload()
	{
		$this->requireAjaxRequest();
		$fieldId = craft()->request->getPost('fieldId', 0);
		$entryId = craft()->request->getPost('entryId', 0);

		if (empty($_FILES['files']) || !isset($_FILES['files']['error'][0]) || $_FILES['files']['error'][0] != 0)
		{
			throw new Exception(Craft::t('The upload failed.'));
		}

		/**
		 * @var AssetsFieldType
		 */
		$field = craft()->fields->populateFieldType(craft()->fields->getFieldById($fieldId));

		if (!($field instanceof AssetsFieldType))
		{
			throw new Exception(Craft::t('That is not an Assets field.'));
		}

		if ($entryId)
		{
			$field->element = craft()->elements->getElementById($entryId);
		}
		else
		{
			$field->element = new EntryModel();
		}

		$targetFolderId = $field->resolveSourcePath();

		try
		{
			craft()->assets->checkPermissionByFolderIds($targetFolderId, 'uploadToAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$fileName = $_FILES['files']['name'][0];
		$fileLocation = AssetsHelper::getTempFilePath(pathinfo($fileName, PATHINFO_EXTENSION));
		move_uploaded_file($_FILES['files']['tmp_name'][0], $fileLocation);

		$fileId = craft()->assets->insertFileByLocalPath($fileLocation, $fileName, $targetFolderId);

		// Render and return
		$element = craft()->elements->getElementById($fileId);
		$html = craft()->templates->render('_elements/element', array('element' => $element));
		$css = craft()->templates->getHeadHtml();
		$this->returnJson(array('html' => $html, 'css' => $css));
	}

	/**
	 * Create a folder.
	 */
	public function actionCreateFolder()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();
		$parentId = craft()->request->getRequiredPost('parentId');
		$folderName = craft()->request->getRequiredPost('folderName');

		try
		{
			craft()->assets->checkPermissionByFolderIds($parentId, 'createSubfoldersInAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = craft()->assets->createFolder($parentId, $folderName);

		$this->returnJson($response->getResponseData());
	}

	/**
	 * Delete a folder.
	 */
	public function actionDeleteFolder()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();
		$folderId = craft()->request->getRequiredPost('folderId');
		$response = craft()->assets->deleteFolderById($folderId);

		try
		{
			craft()->assets->checkPermissionByFolderIds($folderId, 'removeFromAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$this->returnJson($response->getResponseData());

	}

	/**
	 * Rename a folder
	 */
	public function actionRenameFolder()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();

		$folderId = craft()->request->getRequiredPost('folderId');
		$newName = craft()->request->getRequiredPost('newName');

		try
		{
			craft()->assets->checkPermissionByFolderIds($folderId, 'removeFromAssetSource');
			craft()->assets->checkPermissionByFolderIds($folderId, 'createSubfoldersInAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = craft()->assets->renameFolder($folderId, $newName);

		$this->returnJson($response->getResponseData());
	}

	/**
	 * Delete a file or multiple files.
	 */
	public function actionDeleteFile()
	{
		$this->requireLogin();
		$this->requireAjaxRequest();
		$fileIds = craft()->request->getRequiredPost('fileId');

		try
		{
			craft()->assets->checkPermissionByFileIds($fileIds, 'removeFromAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = craft()->assets->deleteFiles($fileIds);
		$this->returnJson($response->getResponseData());
	}

	/**
	 * Move a file or multiple files.
	 */
	public function actionMoveFile()
	{
		$this->requireLogin();

		$fileIds = craft()->request->getRequiredPost('fileId');
		$folderId = craft()->request->getRequiredPost('folderId');
		$fileName = craft()->request->getPost('fileName');
		$actions = craft()->request->getPost('action');

		try
		{
			craft()->assets->checkPermissionByFileIds($fileIds, 'removeFromAssetSource');
			craft()->assets->checkPermissionByFolderIds($folderId, 'uploadToAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = craft()->assets->moveFiles($fileIds, $folderId, $fileName, $actions);
		$this->returnJson($response->getResponseData());
	}

	/**
	 * Move a folder.
	 */
	public function actionMoveFolder()
	{
		$this->requireLogin();

		$folderId = craft()->request->getRequiredPost('folderId');
		$parentId = craft()->request->getRequiredPost('parentId');
		$action = craft()->request->getPost('action');

		try
		{
			craft()->assets->checkPermissionByFolderIds($folderId, 'removeFromAssetSource');
			craft()->assets->checkPermissionByFolderIds($parentId, 'uploadToAssetSource');
			craft()->assets->checkPermissionByFolderIds($parentId, 'createSubfoldersInAssetSource');
		}
		catch (Exception $e)
		{
			$this->returnErrorJson($e->getMessage());
		}

		$response = craft()->assets->moveFolder($folderId, $parentId, $action);

		$this->returnJson($response->getResponseData());
	}

	/**
	 * Generate a transform.
	 */
	public function actionGenerateTransform()
	{
		$transformId = craft()->request->getQuery('transformId');
		$returnUrl = (bool) craft()->request->getPost('returnUrl', false);

		// If transform Id was not passed in, see if file id and handle were.
		if (empty($transformId))
		{
			$fileId = craft()->request->getPost('fileId');
			$handle = craft()->request->getPost('handle');
			$fileModel = craft()->assets->getFileById($fileId);
			$transformIndexModel = craft()->assetTransforms->getTransformIndex($fileModel, $handle);
			$transformId = $transformIndexModel->id;
		}
		else
		{
			$transformIndexModel = craft()->assetTransforms->getTransformIndexModelById($transformId);
		}

		if (!$transformIndexModel)
		{
			throw new Exception(Craft::t('No asset image transform exists with the ID “{id}”', array('id' => $transformId)));
		}

		// Make sure we're not in the middle of working on this transform from a separete request
		if ($transformIndexModel->inProgress)
		{
			for ($safety = 0; $safety < 100; $safety++)
			{
				// Wait a second!
				sleep(1);
				ini_set('max_execution_time', 120);

				$transformIndexModel = craft()->assetTransforms->getTransformIndexModelById($transformId);

				// Is it being worked on right now?
				if ($transformIndexModel->inProgress)
				{
					// Make sure it hasn't been working for more than 30 seconds. Otherwise give up on the other request.
					$time = new DateTime();

					if ($time->getTimestamp() - $transformIndexModel->dateUpdated->getTimestamp() < 30)
					{
						continue;
					}
					else
					{
						$transformIndexModel->dateUpdated = new DateTime();
						craft()->assetTransforms->storeTransformIndexData($transformIndexModel);
						break;
					}
				}
				else
				{
					// Must be done now!
					break;
				}
			}
		}

		if (!$transformIndexModel->fileExists)
		{
			$transformIndexModel->inProgress = 1;
			craft()->assetTransforms->storeTransformIndexData($transformIndexModel);

			$result = craft()->assetTransforms->generateTransform($transformIndexModel);

			if ($result)
			{
				$transformIndexModel->inProgress = 0;
				$transformIndexModel->fileExists = 1;
				craft()->assetTransforms->storeTransformIndexData($transformIndexModel);
			}
			else
			{
				// No source file. Throw a 404.
				$transformIndexModel->inProgress = 0;
				craft()->assetTransforms->storeTransformIndexData($transformIndexModel);
				throw new HttpException(404, Craft::t("The requested image could not be found!"));
			}

		}

		$url = craft()->assetTransforms->getUrlforTransformByIndexId($transformId);
		if ($returnUrl)
		{
			$this->returnJson(array('url' => $url));
		}

		$this->redirect($url, true, 302);
		craft()->end();
	}

	/**
	 * Get information about available transforms.
	 */
	public function actionGetTransformInfo()
	{
		$this->requireAjaxRequest();
		$transforms = craft()->assetTransforms->getAllTransforms();
		$output = array();
		foreach ($transforms as $transform)
		{
			$output[] = (object) array('id' => $transform->id, 'handle' => $transform->handle, 'name' => $transform->name);
		}

		$this->returnJson($output);
	}
}

