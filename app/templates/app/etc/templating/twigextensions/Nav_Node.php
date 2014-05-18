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
 * Represents a nav node.
 */
class Nav_Node extends \Twig_Node_For
{
	protected $navItemNode;

	public function __construct(\Twig_Node_Expression_AssignName $keyTarget, \Twig_Node_Expression_AssignName $valueTarget, \Twig_Node_Expression $seq, \Twig_NodeInterface $upperBody, \Twig_NodeInterface $lowerBody = null, \Twig_NodeInterface $indent = null, \Twig_NodeInterface $outdent = null, $lineno, $tag = null)
	{
		$this->navItemNode = new NavItem_Node($valueTarget, $indent, $outdent, $lowerBody, $lineno, $tag);
		$body = new \Twig_Node(array($this->navItemNode, $upperBody));

		parent::__construct($keyTarget, $valueTarget, $seq, null, $body, null, $lineno, $tag);
	}

	/**
	 * Compiles the node to PHP.
	 *
	 * @param \Twig_Compiler $compiler
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		parent::compile($compiler);

		$compiler
			// Were there any items?
			->write("if (isset(\$_thisItemLevel)) {\n")
			->indent()
				// Remember the current context
				->write("\$_tmpContext = \$context;\n")
				// Close out the unclosed items
				->write("if (\$_thisItemLevel > \$_firstItemLevel) {\n")
				->indent()
					->write("for (\$_i = \$_thisItemLevel; \$_i > \$_firstItemLevel; \$_i--) {\n")
					->indent()
						// Did we output an item at that level?
						->write("if (isset(\$_contextsByLevel[\$_i])) {\n")
						->indent()
							// Temporarily set the context to the element at this level
							->write("\$context = \$_contextsByLevel[\$_i];\n")
							->subcompile($this->navItemNode->getNode('lower_body'), false)
							->subcompile($this->navItemNode->getNode('outdent'), false)
						->outdent()
						->write("}\n")
					->outdent()
					->write("}\n")
				->outdent()
				->write("}\n")
				// Close out the last item
				->write("\$context = \$_contextsByLevel[\$_firstItemLevel];\n")
				->subcompile($this->navItemNode->getNode('lower_body'), false)
				// Set the context back
				->write("\$context = \$_tmpContext;\n")
				// Unset out variables
				->write("unset(\$_thisItemLevel, \$_firstItemLevel, \$_i, \$_contextsByLevel, \$_tmpContext);\n")
			->outdent()
			->write("}\n")
		;
	}
}
