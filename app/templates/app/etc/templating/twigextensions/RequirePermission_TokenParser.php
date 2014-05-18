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
class RequirePermission_TokenParser extends \Twig_TokenParser
{
	/**
	 * Parses {% requirePermission %} tags.
	 *
	 * @param \Twig_Token $token
	 * @return RequirePermission_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$permissionName = $this->parser->getExpressionParser()->parseExpression();
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		return new RequirePermission_Node(array('permissionName' => $permissionName), array(), $lineno, $this->getTag());
	}

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'requirePermission';
	}
}
