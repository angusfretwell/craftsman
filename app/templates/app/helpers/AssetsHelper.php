<?php
namespace Craft;

/**
 * Class AssetsHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class AssetsHelper
{
	// Constants
	// =========================================================================

	const INDEX_SKIP_ITEMS_PATTERN = '/.*(Thumbs\.db|__MACOSX|__MACOSX\/|__MACOSX\/.*|\.DS_STORE)$/i';

	// Public Methods
	// =========================================================================

	/**
	 * Get a temporary file path.
	 *
	 * @param string $extension extension to use. "tmp" by default.
	 *
	 * @return mixed
	 */
	public static function getTempFilePath($extension = 'tmp')
	{
		$extension = strpos($extension, '.') !== false ? pathinfo($extension, PATHINFO_EXTENSION) : $extension;
		$fileName = uniqid('assets', true).'.'.$extension;

		return IOHelper::createFile(craft()->path->getTempPath().$fileName)->getRealPath();
	}

	/**
	 * Generate a URL for a given Assets file in a Source Type.
	 *
	 * @param BaseAssetSourceType $sourceType
	 * @param AssetFileModel $file
	 *
	 * @return string
	 */
	public static function generateUrl(BaseAssetSourceType $sourceType, AssetFileModel $file)
	{
		$baseUrl = $sourceType->getBaseUrl();
		$folderPath = $file->getFolder()->path;
		$fileName = $file->filename;
		$appendix = AssetsHelper::getUrlAppendix($sourceType, $file);

		return $baseUrl.$folderPath.$fileName.$appendix;
	}

	/**
	 * Get appendix for an URL based on it's Source caching settings.
	 *
	 * @param BaseAssetSourceType $source
	 * @param AssetFileModel $file
	 *
	 * @return string
	 */
	public static function getUrlAppendix(BaseAssetSourceType $source, AssetFileModel $file)
	{
		$appendix = '';

		if (!empty($source->getSettings()->expires) && DateTimeHelper::isValidIntervalString($source->getSettings()->expires))
		{
			$appendix = '?mtime='.$file->dateModified->format("YmdHis");
		}

		return $appendix;
	}

	/**
	 * Clean an Asset's filename.
	 *
	 * @param $fileName
	 *
	 * @return mixed
	 */
	public static function cleanAssetName($fileName)
	{
		$separator = craft()->config->get('filenameWordSeparator');

		if (!is_string($separator))
		{
			$separator = null;
		}

		return IOHelper::cleanFilename($fileName, false, $separator);
	}
}
