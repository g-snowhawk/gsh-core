<?php

namespace Gsnowhawk\View\Markdown;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Extension extends AbstractExtension
{
    private $engine;

    public function __construct($markdownEngine)
    {
        $this->engine = $markdownEngine;
    }

    public function getFilters()
    {
        return [
            new TwigFilter(
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
