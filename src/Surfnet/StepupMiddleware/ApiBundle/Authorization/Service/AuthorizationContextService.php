<?php

/**
 * Copyright 2018 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Authorization\Service;

use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContext;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\ConfiguredInstitutionRepository;
use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuthorizationRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SraaService;

/**
 * Creates InstitutionAuthorizationContext
 *
 * The Context is enriched with the 'isSraa' setting. It verifies if the
 * actor id matches that of one of the SRAA's.
 */
class AuthorizationContextService
{
    private SraaService $sraaService;

    private IdentityService $identityService;

    private ConfiguredInstitutionRepository $institutionRepository;

    private AuthorizationRepository $authorizationRepository;

    public function __construct(
        SraaService $sraaService,
        IdentityService $identityService,
        ConfiguredInstitutionRepository $institutionRepository,
        AuthorizationRepository $authorizationRepository
    ) {
        $this->sraaService = $sraaService;
        $this->identityService = $identityService;
        $this->institutionRepository = $institutionRepository;
        $this->authorizationRepository = $authorizationRepository;
    }

    public function buildSelectRaaInstitutionAuthorizationContext(IdentityId $actorId): InstitutionAuthorizationContext
    {
        $isSraa = $this->isSraa($actorId);
        // When building an auth context based on the select raa role, we use another query to retrieve the correct
        // institutions.
        $institutions = $this->authorizationRepository->getInstitutionsForSelectRaaRole($actorId);
        return new InstitutionAuthorizationContext($institutions, $isSraa);
    }

    /**
     * Build the InstitutionAuthorizationContext to be used for authorization filtering on institutions  in queries
     *
     * The additional test is performed to indicate if the actor is SRAA. When the identity is SRAA, all Institutions
     * are added to the InstitutionAuthorizationContext
     */
    public function buildInstitutionAuthorizationContext(
        IdentityId $actorId,
        RegistrationAuthorityRole $role
    ): InstitutionAuthorizationContext {
        $isSraa = $this->isSraa($actorId);
        if ($isSraa) {
            $institutions = new InstitutionCollection();
            $configuredInstitutions = $this->institutionRepository->findAll();
            foreach ($configuredInstitutions as $institution) {
                $institutions->add(new Institution((string)$institution->institution));
            }
        } else {
            // Get the institutions the identity is RA(A) for.
            $institutions = $this->authorizationRepository->getInstitutionsForRole($role, $actorId);
        }
        return new InstitutionAuthorizationContext($institutions, $isSraa);
    }

    private function isSraa(IdentityId $actorId): bool
    {
        $identity = $this->identityService->find((string)$actorId);
        if (!$identity) {
            throw new InvalidArgumentException('The provided id is not associated with any known identity');
        }
        $sraa = $this->sraaService->findByNameId($identity->nameId);
        return !is_null($sraa);
    }
}
