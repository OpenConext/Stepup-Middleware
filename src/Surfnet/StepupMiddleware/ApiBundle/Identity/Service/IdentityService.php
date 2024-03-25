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

use Iterator;
use Pagerfanta\Pagerfanta;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\IdentitySelfAssertedTokenOptions;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\IdentityQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentitySelfAssertedTokenOptionsRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SraaRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IdentityService extends AbstractSearchService
{
    public function __construct(
        private readonly IdentityRepository $repository,
        private readonly IdentitySelfAssertedTokenOptionsRepository $identitySelfAssertedTokensOptionsRepository,
        private readonly RaListingRepository $raListingRepository,
        private readonly SraaRepository $sraaRepository,
    ) {
    }

    public function find(string $id): ?Identity
    {
        return $this->repository->find($id);
    }

    /**
     * @return Pagerfanta<Identity>
     */
    public function search(IdentityQuery $query): Pagerfanta
    {
        $searchQuery = $this->repository->createSearchQuery($query);

        return $this->createPaginatorFrom($searchQuery, $query);
    }

    public function findRegistrationAuthorityCredentialsOf(string $identityId): ?RegistrationAuthorityCredentials
    {
        $identity = $this->find($identityId);

        if (!$identity instanceof Identity) {
            return null;
        }

        return $this->findRegistrationAuthorityCredentialsByIdentity($identity);
    }

    public function findRegistrationAuthorityCredentialsByNameIdAndInstitution(
        NameId $nameId,
        Institution $institution
    ): ?RegistrationAuthorityCredentials {
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
            throw new RuntimeException(
                sprintf(
                    'Found more than one identity matching NameID "%s" within institution "%s"',
                    $nameId->getNameId(),
                    $institution->getInstitution(),
                ),
            );
        }

        /** @var Iterator $collection */
        $collection = $identities->getIterator();

        /** @var Identity $identity */
        $identity = $collection->current();

        return $this->findRegistrationAuthorityCredentialsByIdentity($identity);
    }

    private function findRegistrationAuthorityCredentialsByIdentity(Identity $identity): ?RegistrationAuthorityCredentials
    {
        $raListing = $this->raListingRepository->findByIdentityId(new IdentityId($identity->id));
        $sraa = $this->sraaRepository->findByNameId($identity->nameId);

        if ($raListing !== []) {
            $credentials = RegistrationAuthorityCredentials::fromRaListings($raListing);

            if ($sraa !== null) {
                $credentials = $credentials->grantSraa();
            }

            return $credentials;
        }

        if ($sraa !== null) {
            return RegistrationAuthorityCredentials::fromSraa($sraa, $identity);
        }

        return null;
    }

    public function getSelfAssertedTokenRegistrationOptions(
        Identity $identity,
        bool $hasVettedSecondFactor,
    ): IdentitySelfAssertedTokenOptions {
        $options = $this->identitySelfAssertedTokensOptionsRepository->find($identity->id);
        // Backward compatibility for Identities from the pre SAT era
        if (!$options instanceof IdentitySelfAssertedTokenOptions) {
            $options = new IdentitySelfAssertedTokenOptions();
            // Safe to say they did not have a SAT
            $options->possessedSelfAssertedToken = false;
            // Based on current reality. It could be that the user had a token and then revoked it.
            $options->possessedToken = $hasVettedSecondFactor;
        }
        return $options;
    }
}
