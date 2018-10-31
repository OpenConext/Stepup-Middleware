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

use Surfnet\Stepup\Identity\Value\Institution;

class InstitutionAuthorizationContext implements InstitutionAuthorizationContextInterface
{
    /**
     * @var Institution
     */
    private $actorInstitution;

    /**
     * @var InstitutionRoleSet
     */
    private $roleRequirements;

    /**
     * @var bool
     */
    private $isSraa;

    /**
     * AuthorizationContext constructor.
     * @param Institution $actorInstitution
     * @param InstitutionRoleSetInterface $roleRequirements
     * @param bool $isSraa describes if the actor is SRAA or not. Default: false
     */
    public function __construct(
        Institution $actorInstitution,
        InstitutionRoleSetInterface $roleRequirements,
        $isSraa = false
    ) {
        $this->actorInstitution = $actorInstitution;
        $this->roleRequirements = $roleRequirements;
        $this->isSraa = $isSraa;
    }

    /**
     * @return Institution
     */
    public function getActorInstitution()
    {
        return $this->actorInstitution;
    }

    /**
     * @return InstitutionRoleSet
     */
    public function getRoleRequirements()
    {
        return $this->roleRequirements;
    }

    /**
     * @return bool
     */
    public function isActorSraa()
    {
        return $this->isSraa;
    }
}
