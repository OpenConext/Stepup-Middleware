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
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Filter\InstitutionAuthorizationRepositoryFilter;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\IdentityQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SraaRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;

class IdentityService extends AbstractSearchService
{
    /**
     * @var IdentityRepository
     */
    private $repository;

    /**
     * @var RaListingRepository
     */
    private $raListingRepository;

    /**
     * @var SraaRepository
     */
    private $sraaRepository;
    /**
     * @var InstitutionAuthorizationRepositoryFilter
     */
    private $authorizationRepositoryFilter;

    /**
     * @param IdentityRepository $repository
     * @param RaListingRepository $raListingRepository
     * @param SraaRepository $sraaRepository
     * @param InstitutionAuthorizationRepositoryFilter $authorizationRepositoryFilter The authorization filter is used
     *        to filter the results for a specific institution based on it's given roles
     */
    public function __construct(
        IdentityRepository $repository,
        RaListingRepository $raListingRepository,
        SraaRepository $sraaRepository,
        InstitutionAuthorizationRepositoryFilter $authorizationRepositoryFilter
    ) {
        $this->repository = $repository;
        $this->raListingRepository = $raListingRepository;
        $this->sraaRepository = $sraaRepository;
        $this->authorizationRepositoryFilter = $authorizationRepositoryFilter;
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
     * @param IdentityQuery $query
     * @return \Pagerfanta\Pagerfanta
     */
    public function search(IdentityQuery $query)
    {
        $searchQuery = $this->repository->createSearchQuery($query, $this->authorizationRepositoryFilter);

        $paginator = $this->createPaginatorFrom($searchQuery, $query);

        return $paginator;
    }

    /**
     * @param  string $identityId
     * @return null|RegistrationAuthorityCredentials
     */
    public function findRegistrationAuthorityCredentialsOf($identityId)
    {
        $identity = $this->find($identityId);

        if (!$identity) {
            return null;
        }

        return $this->findRegistrationAuthorityCredentialsByIdentity($identity);
    }

    /**
     * @param NameId $nameId
     * @param Institution $institution
     * @return RegistrationAuthorityCredentials|null
     */
    public function findRegistrationAuthorityCredentialsByNameIdAndInstitution(NameId $nameId, Institution $institution)
    {
        $query = new IdentityQuery();
        $query->nameId = $nameId->getNameId();
        $query->institution = $institution->getInstitution();
        $query->pageNumber = 1;
        $query->itemsPerPage = 2;

        $identities = $this->search($query);
        $identityCount = count($identities);

        if ($identityCount === 0) {
            return null;
        }

        if ($identityCount > 1) {
            throw new RuntimeException(sprintf(
                'Found more than one identity matching NameID "%s" within institution "%s"',
                $nameId->getNameId(),
                $institution->getInstitution()
            ));
        }

        /** @var Identity $identity */
        $identity = $identities->getIterator()->current();

        return $this->findRegistrationAuthorityCredentialsByIdentity($identity);
    }

    /**
     * @param Identity $identity
     * @return null|RegistrationAuthorityCredentials
     */
    private function findRegistrationAuthorityCredentialsByIdentity(Identity $identity)
    {
        $raListing = $this->raListingRepository->findByIdentityId(new IdentityId($identity->id));
        $sraa = $this->sraaRepository->findByNameId($identity->nameId);

        if ($raListing) {
            $credentials = RegistrationAuthorityCredentials::fromRaListing($raListing);

            if ($sraa) {
                $credentials = $credentials->grantSraa();
            }

            return $credentials;
        }

        if ($sraa) {
            return RegistrationAuthorityCredentials::fromSraa($sraa, $identity);
        }

        return null;
    }
}
