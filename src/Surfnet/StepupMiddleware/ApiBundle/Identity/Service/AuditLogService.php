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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchAuditLogCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository;

class AuditLogService extends AbstractSearchService
{
    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository
     */
    private $repository;

    public function __construct(AuditLogRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param SearchAuditLogCommand $command
     * @return Pagerfanta
     */
    public function search(SearchAuditLogCommand $command)
    {
        $query = $this->repository->createSearchQuery($command);

        $paginator = $this->createPaginatorFrom($query, $command);

        return $paginator;
    }
}
