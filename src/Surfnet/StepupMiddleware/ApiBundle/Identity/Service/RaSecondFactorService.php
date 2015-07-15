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

use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaSecondFactorQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaSecondFactorRepository;

class RaSecondFactorService extends AbstractSearchService
{
    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaSecondFactorRepository
     */
    private $repository;

    /**
     * @param RaSecondFactorRepository $repository
     */
    public function __construct(RaSecondFactorRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param RaSecondFactorQuery $query
     * @return \Pagerfanta\Pagerfanta
     */
    public function search(RaSecondFactorQuery $query)
    {
        $doctrineQuery = $this->repository->createSearchQuery($query);

        $paginator = $this->createPaginatorFrom($doctrineQuery, $query);

        return $paginator;
    }
}
