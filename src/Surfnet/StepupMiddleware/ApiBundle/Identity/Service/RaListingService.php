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

use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaListingQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;

class RaListingService extends AbstractSearchService
{
    /**
     * @var RaListingRepository
     */
    private $raListingRepository;

    public function __construct(RaListingRepository $raListingRepository)
    {
        $this->raListingRepository = $raListingRepository;
    }

    /**
     * @param IdentityId $identityId
     * @return null|\Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing
     */
    public function findByIdentityId(IdentityId $identityId)
    {
        return $this->raListingRepository->findByIdentityId($identityId);
    }

    public function findByIdentityIdAndRaInstitution(IdentityId $identityId, Institution $raInstitution)
    {
        return $this->raListingRepository->findByIdentityIdAndRaInstitution($identityId, $raInstitution);
    }

    /**
     * @param RaListingQuery $query
     * @return \Pagerfanta\Pagerfanta
     */
    public function search(RaListingQuery $query)
    {
        $doctrineQuery = $this->raListingRepository->createSearchQuery($query);

        $paginator = $this->createPaginatorFrom($doctrineQuery, $query);

        return $paginator;
    }

    /**
     * @param Institution $institution
     * @return RegistrationAuthorityCredentials[]
     */
    public function listRegistrationAuthoritiesFor(Institution $institution)
    {
        $raListings = $this->raListingRepository->listRasFor($institution);

        return $raListings
            ->map(function (RaListing $raListing) {
                return RegistrationAuthorityCredentials::fromRaListing($raListing);
            })
            ->toArray();
    }
}
