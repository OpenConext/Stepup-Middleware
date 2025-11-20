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

use Pagerfanta\Pagerfanta;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaSecondFactorQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaSecondFactorRepository;

/** @extends AbstractSearchService<RaSecondFactor> */
class RaSecondFactorService extends AbstractSearchService
{
    public function __construct(private readonly RaSecondFactorRepository $repository)
    {
    }

    /**
     * @return Pagerfanta<RaSecondFactor>
     */
    public function search(RaSecondFactorQuery $query): Pagerfanta
    {
        $doctrineQuery = $this->repository->createSearchQuery($query);

        return $this->createPaginatorFrom($doctrineQuery, $query);
    }

    /**
     * @return array
     */
    public function getFilterOptions(RaSecondFactorQuery $query): array
    {
        return $this->getFilteredQueryOptions($this->repository->createOptionsQuery($query));
    }

    /**
     * @return array
     */
    public function searchUnpaginated(RaSecondFactorQuery $query): array
    {
        return $this->repository->createSearchQuery($query)->getResult();
    }
}
