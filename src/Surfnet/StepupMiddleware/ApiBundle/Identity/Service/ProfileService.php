<?php

/**
 * Copyright 2019 SURFnet B.V.
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

use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationContextService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorityRole;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorizedInstitutionCollection;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\Profile;
use function sprintf;

class ProfileService extends AbstractSearchService
{
    /**
     * @var RaListingRepository
     */
    private $raListingRepository;

    /**
     * @var IdentityService
     */
    private $identityService;

    /**
     * @var AuthorizationContextService
     */
    private $authorizationService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        RaListingRepository $raListingRepository,
        IdentityService $identityService,
        AuthorizationContextService $institutionAuthorizationService,
        LoggerInterface $logger
    ) {
        $this->raListingRepository = $raListingRepository;
        $this->identityService = $identityService;
        $this->authorizationService = $institutionAuthorizationService;
        $this->logger = $logger;
    }

    /**
     * Uses the identityId to first load the ra credentials (if present)
     * These credentials are then used to test what type of administrator we are dealing with ((S)RA(A)). Next the
     * authorizations are retrieved from the InstitutionAuthorizationRepository. Not that we distinguish between
     * implicit and explicitly appointed roles. The implicit roles are based on the institution configuration
     * (SELECT_RAA). Finally identity is retrieved for the provided identityId. This data is then merged in a Profile
     * value object.
     *
     * When the profile is incorrect, for example because no identity can be found, null is returned instead of a
     * Profile. Its possible to retrieve profile data for a non RA user, in that case no authorization data is set
     * on the profile. The same goes for the SRAA user. As that user is allowed all authorizations for all institutions.
     * An additional isSraa flag is set to true for these administrators.
     *
     * @param string $identityId
     * @return Profile|null
     */
    public function createProfile($identityId)
    {
        $identity = $this->identityService->find($identityId);

        if ($identity === null) {
            $this->logger->notice(sprintf('No Identity found with IdentityId %s', $identityId));
            return null;
        }
        $this->logger->notice(sprintf('Found IdentityId "%s" NameId "%s"', $identityId, $identity->nameId ));

        $raListing = $this->raListingRepository->findByIdentityId(new IdentityId($identityId));
        $isRa = $this->getRoleFromListing($raListing, AuthorityRole::ROLE_RA);
        $isRaa = $this->getRoleFromListing($raListing, AuthorityRole::ROLE_RAA);

        $this->logger->notice(
            sprintf(
                'Based on RaListing Identity %s has roles(RA: %s, RAA: %s)',
                $identityId,
                $isRa ? "YES" : "NO",
                $isRaa ? "YES" : "NO"
            )
        );


        if ($raListing === null) {
            $this->logger->notice(sprintf('No RA listing found for IdentityId %s', $identityId));
            return null;
        }

        $authorizationContextRa = $this->authorizationService->buildInstitutionAuthorizationContext(
            new IdentityId($identityId),
            InstitutionRole::useRa()
        );
        $authorizations = AuthorizedInstitutionCollection::from(
            $authorizationContextRa->getInstitutions()
        );

        $this->logger->notice(sprintf('IdentityId "%s" is RA for: %s', $identityId, json_encode($authorizationContextRa->getInstitutions()->jsonSerialize())));

        if ($isRaa) {
            $authorizationContextRaa = $this->authorizationService->buildInstitutionAuthorizationContext(
                new IdentityId($identityId),
                InstitutionRole::useRaa()
            );

            $this->logger->notice(sprintf('IdentityId "%s" is RAA for: %s', $identityId, json_encode($authorizationContextRaa->getInstitutions()->jsonSerialize())));

            $authorizations = AuthorizedInstitutionCollection::from(
                $authorizationContextRa->getInstitutions(),
                $authorizationContextRaa->getInstitutions()
            );
        }

        return new Profile(
            $identity,
            $authorizations,
            $authorizationContextRa->isActorSraa()
        );
    }

    /**
     * @param array<int, RaListing> $raListing
     */
    private function getRoleFromListing(array $raListing, string $role): bool
    {
        foreach ($raListing as $listing) {
            if ($listing->role->getRole() === $role) {
                return true;
            }
        }
        return false;
    }
}
