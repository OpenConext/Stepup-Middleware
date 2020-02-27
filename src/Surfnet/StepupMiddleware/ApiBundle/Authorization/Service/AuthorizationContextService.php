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

use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContext;
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
    /**
     * @var SraaService
     */
    private $sraaService;

    /**
     * @var IdentityService
     */
    private $identityService;
    /**
     * @var AuthorizationRepository
     */
    private $authorizationRepository;

    public function __construct(
        SraaService $sraaService,
        IdentityService $identityService,
        AuthorizationRepository $authorizationRepository
    ) {
        $this->sraaService = $sraaService;
        $this->identityService = $identityService;
        $this->authorizationRepository = $authorizationRepository;
    }

    /**
     * Build the InstitutionAuthorizationContext to be used for authorization filtering on institutions  in queries
     *
     * The additional test is performed to indicate if the actor is SRAA.
     *
     * @param IdentityId $actorId
     * @param InstitutionRole $role
     * @return InstitutionAuthorizationContext
     */
    public function buildInstitutionAuthorizationContext(IdentityId $actorId, InstitutionRole $role)
    {
        $identity = $this->identityService->find((string) $actorId);

        if (!$identity) {
            throw new InvalidArgumentException('The provided id is not associated with any known identity');
        }

        $sraa = $this->sraaService->findByNameId($identity->nameId);
        $isSraa = !is_null($sraa);

        $institutions = $this->authorizationRepository->getInstitutionsForRole($role, $actorId);

        return new InstitutionAuthorizationContext($institutions, $isSraa);
    }
}