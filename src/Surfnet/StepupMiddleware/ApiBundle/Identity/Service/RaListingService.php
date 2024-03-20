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
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaListingQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;

class RaListingService extends AbstractSearchService
{
    public function __construct(private readonly RaListingRepository $raListingRepository)
    {
    }

    /**
     * @return null|RaListing
     */
    public function findByIdentityIdAndRaInstitutionWithContext(
        IdentityId $identityId,
        Institution $raInstitution,
        InstitutionAuthorizationContextInterface $authorizationContext,
    ): ?RaListing
    {
        return $this->raListingRepository->findByIdentityIdAndRaInstitutionWithContext(
            $identityId,
            $raInstitution,
            $authorizationContext,
        );
    }

    /**
     * @return Pagerfanta
     */
    public function search(RaListingQuery $query): Pagerfanta
    {
        $doctrineQuery = $this->raListingRepository->createSearchQuery($query);

        return $this->createPaginatorFrom($doctrineQuery, $query);
    }

    /**
     * @return array
     */
    public function getFilterOptions(RaListingQuery $query): array
    {
        return $this->getFilteredQueryOptions($this->raListingRepository->createOptionsQuery($query));
    }

    /**
     * @return RegistrationAuthorityCredentials[]
     */
    public function listRegistrationAuthoritiesFor(Institution $institution): array
    {
        $raListings = $this->raListingRepository->listRasFor($institution);

        return $raListings
            ->map(fn(RaListing $raListing) => RegistrationAuthorityCredentials::fromRaListing($raListing))
            ->toArray();
    }
}
