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

use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchIdentityCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;

class IdentityService extends AbstractSearchService
{
    /**
     * @var IdentityRepository
     */
    private $repository;

    /**
     * @param IdentityRepository $repository
     */
    public function __construct(IdentityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param string $id
     * @return \Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity|null
     */
    public function find($id)
    {
        return $this->repository->find($id);
    }

    /**
     * @param SearchIdentityCommand $command
     * @return \Pagerfanta\Pagerfanta
     */
    public function search(SearchIdentityCommand $command)
    {
        $searchQuery = $this->repository->createSearchQuery($command);

        $paginator = $this->createPaginatorFrom($searchQuery, $command);

        return $paginator;
    }
}
