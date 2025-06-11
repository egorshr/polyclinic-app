<?php
// src/Doctrine/GROUP_CONCAT.php

namespace App\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType; // <-- ВАЖНО: импортируем TokenType

class GROUP_CONCAT extends FunctionNode
{
    public bool $isDistinct = false;
    public ?Node $pathExp = null;
    public ?Node $separator = null;
    public ?Node $orderBy = null;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $result = 'GROUP_CONCAT(' . ($this->isDistinct ? 'DISTINCT ' : '') .
            $this->pathExp->dispatch($sqlWalker);

        if ($this->orderBy instanceof OrderByClause) {
            $result .= ' ' . $sqlWalker->walkOrderByClause($this->orderBy);
        }

        if ($this->separator) {
            $result .= ' SEPARATOR ' . $sqlWalker->walkStringPrimary($this->separator);
        }

        $result .= ')';

        return $result;
    }

    public function parse(Parser $parser): void
    {
        $lexer = $parser->getLexer();

        $parser->match(TokenType::T_IDENTIFIER); // Имя функции
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        if ($lexer->lookahead->type === TokenType::T_DISTINCT) {
            $parser->match(TokenType::T_DISTINCT);
            $this->isDistinct = true;
        }

        $this->pathExp = $parser->StringPrimary();

        if ($lexer->lookahead->type === TokenType::T_ORDER) {
            $this->orderBy = $parser->OrderByClause();
        }

        if ($lexer->lookahead->type === TokenType::T_COMMA) {
            $parser->match(TokenType::T_COMMA);
            // Проверяем, что следующий токен - это 'SEPARATOR'
            if ($lexer->lookahead->type === TokenType::T_IDENTIFIER && strtoupper($lexer->lookahead->value) === 'SEPARATOR') {
                $parser->match(TokenType::T_IDENTIFIER);
                $this->separator = $parser->StringPrimary();
            }
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}