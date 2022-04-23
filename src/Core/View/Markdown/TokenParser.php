<?php

namespace Gsnowhawk\View\Markdown;

class TokenParser extends \Twig_TokenParser
{
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();

        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideMarkdownEnd'], true);
        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

        return new Node($body, $lineno, $this->getTag());
    }

    public function decideMarkdownEnd(\Twig_Token $token)
    {
        return $token->test('endmarkdown');
    }

    public function getTag()
    {
        return 'markdown';
    }
}
