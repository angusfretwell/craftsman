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

craft()->requireEdition(Craft::Pro);

/**
 * User permission functions
 */
class UserPermissionsVariable
{
	/**
	 * Returns all of the known permissions, sorted by category.
	 *
	 * @return array
	 */
	public function getAllPermissions()
	{
		return craft()->userPermissions->getAllPermissions();
	}

	/**
	 * Returns all of the group permissions a given user has.
	 *
	 * @param int $userId
	 * @return array
	 */
	public function getGroupPermissionsByUserId($userId)
	{
		return craft()->userPermissions->getGroupPermissionsByUserId($userId);
	}
}
