<?php

namespace Gsnowhawk\View\Markdown;

/**
 * Represents a markdown node.
 *
 * It parses content as Markdown.
 *
 * @author Gunnar Lium <gunnar@aptoma.com>
 * @author Joris Berthelot <joris@berthelot.tel>
 */
class Node extends \Twig_Node
{
    public function __construct(\Twig_Node $body, $lineno, $tag = 'markdown')
    {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }

    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('ob_start();'.PHP_EOL)
            ->subcompile($this->getNode('body'))
            ->write('$content = ob_get_clean();'.PHP_EOL)
            ->write('preg_match("/^\s*/", $content, $matches);'.PHP_EOL)
            ->write('$lines = explode("\n", $content);'.PHP_EOL)
            ->write('$content = preg_replace(\'/^\' . $matches[0]. \'/\', "", $lines);'.PHP_EOL)
            ->write('$content = join("\n", $content);'.PHP_EOL)
            ->write('echo $this->env->getExtension(\'markdown\')->parseMarkdown($content);'.PHP_EOL);
    }
}
