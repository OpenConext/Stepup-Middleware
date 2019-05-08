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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Service;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\AbstractQuery;

class AbstractSearchService
{
    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\ORM\Query $doctrineQuery
     * @param AbstractQuery                          $query
     * @return Pagerfanta
     */
    protected function createPaginatorFrom($doctrineQuery, AbstractQuery $query)
    {
        $queryObject = $doctrineQuery;
        if ($doctrineQuery instanceof QueryBuilder) {
            $queryObject = $doctrineQuery->getQuery();
        }

        if (!$queryObject instanceof Query) {
            throw InvalidArgumentException::invalidType(
                'Doctrine\ORM\Query or Doctrine\ORM\QueryBuilder',
                'searchQuery',
                $doctrineQuery
            );
        }

        $adapter   = new DoctrineORMAdapter($doctrineQuery);
        $paginator = new Pagerfanta($adapter);
        $paginator->setMaxPerPage($query->itemsPerPage);
        $paginator->setCurrentPage($query->pageNumber);

        return $paginator;
    }

    /**
     * @param Query $doctrineQuery
     * @return array
     */
    protected function getFilteredQueryOptions(Query $doctrineQuery)
    {
        $filters = [];
        $results = $doctrineQuery->getArrayResult();
        foreach ($results as $options) {
            foreach ($options as $key => $value) {
                $val = (string)$value;
                $filters[$key][$val] = (string)$val;
            }
        }

        foreach (array_keys($filters) as $key) {
            asort($filters[$key]);
        }

        return $filters;
    }
}
