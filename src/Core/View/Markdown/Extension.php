<?php

namespace Gsnowhawk\View\Markdown;

class Extension extends \Twig_Extension
{
    private $engine;

    public function __construct($markdownEngine)
    {
        $this->engine = $markdownEngine;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'markdown',
                [$this, 'parseMarkdown'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function parseMarkdown($content)
    {
        return $this->engine->parse($content);
    }

    public function getTokenParsers()
    {
        return [new TokenParser()];
    }

    public function getName()
    {
        return 'markdown';
    }
}
