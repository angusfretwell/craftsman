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
class SecurityService extends \CSecurityManager
{
	private $_blowFishHashCost;

	/**
	 *
	 */
	function __construct()
	{
		parent::init();
		$this->_blowFishHashCost = craft()->config->get('blowfishHashCost');
	}

	/**
	 * @return int
	 */
	public function getMinimumPasswordLength()
	{
		return 6;
	}

	/**
	 * Hashes a given password with the blowfish encryption algorithm.
	 *
	 * @param      $string        The string to hash
	 * @param bool $validateHash  If you want to validate the just generated hash. Will throw an exception is validation fails.
	 * @throws Exception
	 * @return string             The hash.
	 */
	public function hashPassword($string, $validateHash = false)
	{
		$hash = \CPasswordHelper::hashPassword($string, $this->_blowFishHashCost);

		if ($validateHash)
		{
			if (!$this->checkPassword($string, $hash))
			{
				throw new Exception(Craft::t('Could not hash the given string.'));
			}
		}

		return $hash;
	}

	/**
	 * Validates a blowfish hash against a given string for sameness.
	 *
	 * @param $string
	 * @param $storedHash
	 * @return bool
	 */
	public function checkPassword($string, $storedHash)
	{
		return \CPasswordHelper::verifyPassword($string, $storedHash);
	}
}
