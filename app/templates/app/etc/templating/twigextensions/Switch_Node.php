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

/*
 * Switch node.
 *
 * Based on the rejected Twig pull request: https://github.com/fabpot/Twig/pull/185
 */
class Switch_Node extends \Twig_Node
{
    private $_cases;

    public function __construct(\Twig_NodeInterface $value, \Twig_NodeInterface $cases, \Twig_NodeInterface $default = null, $lineno, $tag = null)
    {
        $this->_cases = $cases;

        parent::__construct(array('value' => $value, 'default' => $default), array(), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param \Twig_Compiler $compiler
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
			->write("switch (")
			->subcompile($this->getNode('value'))
			->raw(") {\n")
			->indent();

        foreach ($this->_cases as $case)
        {
            $compiler
				->write('case ')
                ->subcompile($case['expr'])
                ->raw(":\n")
                ->write("{\n")
                ->indent()
                ->subcompile($case['body'])
                ->write("break;\n")
				->outdent()
                ->write("}\n");
        }

        if ($this->hasNode('default') && $this->getNode('default') !== null)
        {
            $compiler
                ->write("default:\n")
                ->write("{\n")
                ->indent()
                ->subcompile($this->getNode('default'))
				->outdent()
                ->write("}\n");
        }

        $compiler
            ->outdent()
            ->write("}\n");
    }
}