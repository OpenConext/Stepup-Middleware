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

use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaCandidateQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaCandidateRepository;

class RaCandidateService extends AbstractSearchService
{
    /**
     * @var RaCandidateRepository
     */
    private $raCandidateRepository;

    /**
     * @param RaCandidateRepository $raCandidateRepository
     */
    public function __construct(RaCandidateRepository $raCandidateRepository)
    {
        $this->raCandidateRepository = $raCandidateRepository;
    }

    /**
     * @param RaCandidateQuery $query
     * @return \Pagerfanta\Pagerfanta
     */
    public function search(RaCandidateQuery $query)
    {
        $doctrineQuery = $this->raCandidateRepository->createSearchQuery($query);

        $paginator = $this->createPaginatorFrom($doctrineQuery, $query);

        return $paginator;
    }

    /**
     * @param RaCandidateQuery $query
     * @return array
     */
    public function getFilterOptions(RaCandidateQuery $query)
    {
        $doctrineQuery = $this->raCandidateRepository->createOptionsQuery($query);

        $filters = [];
        $results = $doctrineQuery->getArrayResult();
        foreach ($results as $options) {
            foreach ($options as $key => $value) {
                $val = (string)$value;
                $filters[$key][$val] = (string)$val;
            }
        }

        return $filters;
    }

    /**
     * @param string $identityId
     * @param InstitutionAuthorizationContextInterface $authorizationContext
     * @return null|\Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaCandidate[]
     */
    public function findByIdentityIdAndRaInstitution($identityId, InstitutionAuthorizationContextInterface $authorizationContext)
    {
        $raCandidates = $this->raCandidateRepository->findAllRaasByIdentityIdAndRaInstitution($identityId, $authorizationContext);

        return $raCandidates;
    }
}
