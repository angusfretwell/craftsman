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
class m140401_000021_client_user extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		if (!craft()->db->columnExists('users', 'client'))
		{
			$this->addColumnAfter('users', 'client', array('column' => ColumnType::Bool, 'null' => false), 'admin');
		}
		else
		{
			Craft::log('Tried to add the `client` column to the `users` table, but it already exists.', LogLevel::Info, true);
		}

		return true;
	}
}
