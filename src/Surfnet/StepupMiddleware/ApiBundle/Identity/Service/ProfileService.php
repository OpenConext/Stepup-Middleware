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

use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\InstitutionListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\RaListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorizedInstitutionCollection;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\Profile;

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
     * @var InstitutionListingRepository
     */
    private $institutionListingRepository;

    public function __construct(
        RaListingRepository $raListingRepository,
        InstitutionListingRepository $institutionListingRepository,
        IdentityService $identityService
    ) {
        $this->raListingRepository = $raListingRepository;
        $this->institutionListingRepository = $institutionListingRepository;
        $this->identityService = $identityService;
    }

    /**
     * Uses the identityId to first load the ra credentials (if present)
     * These credentials are then used to test what type of administrator we are dealing with ((S)RA(A)). Next the
     * authorizations are retrieved from the InstitutionAuthorizationRepository. Finally identity is retrieved for the
     * provided identityId. This data is then merged in a Profile value object.
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
        $raCredentials = $this->identityService->findRegistrationAuthorityCredentialsOf($identityId);
        $isSraa = false;
        if ($raCredentials) {
            $isSraa = $raCredentials->isSraa();
            if (!$isSraa && ($raCredentials->isRa() || $raCredentials->isRaa())) {
                $authorizations = $this->findAuthorizationsBy(
                    new IdentityId($raCredentials->getIdentityId())
                );
            }
        }

        $identity = $this->identityService->find($identityId);
        if ($identity === null) {
            return null;
        }

        // If the user is not authorized at all (non ra user), or when the user is SRAA, then build an empty collection.
        if (!isset($authorizations)) {
            $authorizations = new AuthorizedInstitutionCollection($identity->institution);
        }

        return new Profile($identity, $authorizations, $isSraa);
    }

    /**
     * @param IdentityId $identity
     * @return AuthorizedInstitutionCollection
     */
    private function findAuthorizationsBy(IdentityId $identity)
    {
        $ra = new InstitutionRole(InstitutionRole::ROLE_USE_RA);
        $raa = new InstitutionRole(InstitutionRole::ROLE_USE_RAA);

        // Find implicit ra(a) institutions
        $implicitRaInstitutions = $this->institutionListingRepository->getImplicitInstitutionsFor($identity, $ra);
        $implicitRaaInstitutions = $this->institutionListingRepository->getImplicitInstitutionsFor($identity, $raa);

        // Find explicit (appointed) ra(a) institutions
        $raInstitutions = $this->raListingRepository->getExplicitInstitutionsFor($identity, 'ra');
        $raaInstitutions = $this->raListingRepository->getExplicitInstitutionsFor($identity, 'raa');

        // Merge the results (for now we do not want to distinguish between them)
        $raInstitutions->merge($implicitRaInstitutions);
        if (!$raaInstitutions->isEmpty()) {
            $raaInstitutions->merge($implicitRaaInstitutions);
            return AuthorizedInstitutionCollection::from($raInstitutions, $raaInstitutions);
        }
        return AuthorizedInstitutionCollection::from($raInstitutions);
    }
}
