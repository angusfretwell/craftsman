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
class m131212_000001_add_missing_fk_to_emailmessages extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		MigrationHelper::refresh();

		Craft::log('Dropping FK if it exists.', LogLevel::Info, true);
		MigrationHelper::dropForeignKeyIfExists('emailmessages', array('locale'));

		Craft::log('Adding FK to emailmessages table.', LogLevel::Info, true);
		$this->addForeignKey('emailmessages', 'locale', 'locales', 'locale', 'CASCADE', 'CASCADE');

		return true;
	}
}
