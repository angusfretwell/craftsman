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
class RequirePermission_Node extends \Twig_Node
{
	/**
	 * Compiles a RequirePermission_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
		    ->addDebugInfo($this)
		    ->write('\Craft\craft()->userSession->requirePermission(')
		    ->subcompile($this->getNode('permissionName'))
		    ->raw(");\n");
	}
}
