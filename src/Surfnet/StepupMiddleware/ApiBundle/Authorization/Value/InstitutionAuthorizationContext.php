<?php

/**
 * Copyright 2018 SURFnet bv
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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;

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
     * AuthorizationContext constructor.
     * @param string $actorId
     * @param string $actorInstitution
     * @param InstitutionRoleSetInterface $roleRequirements
     */
    public function __construct($actorInstitution, InstitutionRoleSetInterface $roleRequirements)
    {
        $this->actorInstitution = $actorInstitution;
        $this->roleRequirements = $roleRequirements;
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
}
