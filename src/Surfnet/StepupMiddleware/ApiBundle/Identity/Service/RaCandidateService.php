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
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContext;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaCandidate;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaCandidateQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaCandidateRepository;

class RaCandidateService extends AbstractSearchService
{
    public function __construct(private readonly RaCandidateRepository $raCandidateRepository)
    {
    }

    /**
     * @return Pagerfanta<RaCandidate>
     */
    public function search(RaCandidateQuery $query): Pagerfanta
    {
        $doctrineQuery = $this->raCandidateRepository->createSearchQuery($query);

        return $this->createPaginatorFrom($doctrineQuery, $query, false);
    }

    /**
     * @return array
     */
    public function getFilterOptions(RaCandidateQuery $query): array
    {
        return $this->getFilteredQueryOptions($this->raCandidateRepository->createOptionsQuery($query));
    }

    /**
     * @return null|array
     */
    public function findOneByIdentityId(string $identityId): ?array
    {
        return $this->raCandidateRepository->findOneByIdentityId($identityId);
    }

    /**
     * Set the RA candidates USE RA(A) institutions on the Identity he is going to promote.
     * @return non-empty-array[]
     */
    public function setUseRaInstitutionsOnRaCandidate(InstitutionAuthorizationContext $actor, array $raCandidate): array
    {
        $result = [];
        foreach ($actor->getInstitutions() as $raInstitution) {
            $raCandidate['ra_institution'] = new Institution($raInstitution->getInstitution());
            $result[] = $raCandidate;
        }
        return $result;
    }
}
