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
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140403_000000_allow_orphan_assets_again extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{

		// Do this again because for fresh 2.0 installs, these will still be set to NOT NULL
		$this->alterColumn('assetfolders', 'sourceId', array('column' => ColumnType::Int, 'required' => false));
		$this->alterColumn('assetfiles', 'sourceId', array('column' => ColumnType::Int, 'required' => false));

		return true;
	}
}
