<?php

namespace Gsnowhawk\View\Markdown;

use Twig\TokenParser\AbstractTokenParser;
use Twig\Token;

class TokenParser extends AbstractTokenParser
{
    public function parse(Token $token)
    {
        $lineno = $token->getLine();

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideMarkdownEnd'], true);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new Node($body, $lineno, $this->getTag());
    }

    public function decideMarkdownEnd(Token $token)
    {
        return $token->test('endmarkdown');
    }

    public function getTag()
    {
        return 'markdown';
    }
}
