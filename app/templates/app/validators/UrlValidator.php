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
class UrlValidator extends \CUrlValidator
{
	/**
	 * Override the $pattern regex so that a TLD is not required, and the protocol may be relative.
	 */
	public $pattern = '/^(?:(?:{schemes}:)?\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)?|\/)[^\s]*$/i';

	/**
	 * Add support for protocol-relative URLs.
	 *
	 * @see http://paulirish.com/2010/the-protocol-relative-url/
	 */
	public function validateValue($value)
	{
		// Ignore URLs with any environment variables in them
		if (mb_strpos($value, '{') !== false)
		{
			return $value;
		}

		if ($this->defaultScheme !== null && strncmp($value, '/', 1) === 0)
		{
			$this->defaultScheme = null;
		}

		return parent::validateValue($value);
	}
}
