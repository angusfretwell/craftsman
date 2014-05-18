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

craft()->requireEdition(Craft::Client);

/**
 * Rebranding functions
 */
class RebrandVariable
{
	private $_logoPath;
	private $_logoVariable;

	/**
	 * Returns whether a custom logo has been uploaded.
	 *
	 * @return bool
	 */
	public function isLogoUploaded()
	{
		return ($this->_getLogoPath() !== false);
	}

	/**
	 * Returns the logo variable, or false if a logo hasn't been uploaded.
	 *
	 * @return LogoVariable
	 */
	public function getLogo()
	{
		if (!isset($this->_logoVariable))
		{
			$logoPath = $this->_getLogoPath();

			if ($logoPath !== false)
			{
				$this->_logoVariable = new LogoVariable($logoPath);
			}
			else
			{
				$this->_logoVariable = false;
			}
		}

		return $this->_logoVariable;
	}

	/**
	 * Returns the path to the logo, or false if a logo hasn't been uploaded.
	 *
	 * @access private
	 * @return string
	 */
	private function _getLogoPath()
	{
		if (!isset($this->_logoPath))
		{
			$files = IOHelper::getFolderContents(craft()->path->getStoragePath().'logo/', false);
			if (!empty($files))
			{
				$this->_logoPath = $files[0];
			}
			else
			{
				$this->_logoPath = false;
			}
		}

		return $this->_logoPath;
	}
}
