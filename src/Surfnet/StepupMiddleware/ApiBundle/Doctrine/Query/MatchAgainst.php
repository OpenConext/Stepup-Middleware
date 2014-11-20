<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupMiddleware\ApiBundle\Doctrine\Query;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class MatchAgainst extends FunctionNode
{
    /**
     * @var \Doctrine\ORM\Query\AST\PathExpression[]
     */
    private $columns = [];

    /**
     * @var \Doctrine\ORM\Query\AST\InputParameter
     */
    private $searchTerm;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        while (!$parser->getLexer()->isNextToken(Lexer::T_INPUT_PARAMETER)) {
            $this->columns[] = $parser->StateFieldPathExpression();
            $parser->match(Lexer::T_COMMA);
        }

        // Got an input parameter
        $this->searchTerm = $parser->InputParameter();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker)
    {
        $parsedColumns = null;

        $first = true;
        foreach ($this->columns as $column) {
            $first ? $first = false : $parsedColumns .= ', ';
            $parsedColumns .= $column->dispatch($sqlWalker);
        }

        return sprintf(
            "MATCH(%s) AGAINST (%s IN NATURAL LANGUAGE MODE)",
            $parsedColumns,
            $this->searchTerm->dispatch($sqlWalker)
        );
    }
}
