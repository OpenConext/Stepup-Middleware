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

namespace Surfnet\StepupMiddleware\ApiBundle\Authorization\Value;

use Surfnet\Stepup\Identity\Collection\InstitutionCollection;

/**
 * The InstitutionAuthorizationContext states which institutions are selected to perform
 * an action on. Which actions can be performed is determined by the required role the
 * command will require (RA or RAA).
 *
 * In addition, the context also states if the identity initiating this context is SRAA.
 * If that's the case. The Identity will be allowed access to all institutions.
 *
 * For implementation details see:
 *  - Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationContextService
 *  - Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuthorizationRepository
 */
class InstitutionAuthorizationContext implements InstitutionAuthorizationContextInterface
{
    private ?InstitutionCollection $institutions;

    private bool $isSraa;

    public function __construct(
        InstitutionCollection $institutions = null,
        bool $isSraa = false
    ) {
        $this->institutions = $institutions;
        $this->isSraa = $isSraa;
    }

    public function getInstitutions(): InstitutionCollection
    {
        return $this->institutions;
    }

    public function isActorSraa(): bool
    {
        return $this->isSraa;
    }
}
