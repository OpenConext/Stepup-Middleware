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

namespace Surfnet\StepupMiddleware\ApiBundle\Configuration\Service;

use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionAuthorizationOption;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;

final class InstitutionAuthorizationOptionMap
{
    /**
     * @var InstitutionAuthorizationOption[]
     */
    private array $institutionOptions = [];

    /**
     * InstitutionAuthorizationOptionMap constructor.
     * @param Institution $institution
     * @param InstitutionAuthorization[] $institutionAuthorizations
     */
    private function __construct(Institution $institution, array $institutionAuthorizations)
    {
        $institutions = [];
        $roles = [];
        foreach ($institutionAuthorizations as $authorization) {
            // Skip if the authorization is from a different institution
            if (!$authorization->institution->equals($institution)) {
                continue;
            }

            $role = $authorization->institutionRole;
            if (!isset($roles[$role->getType()])) {
                $roles[$role->getType()] = $role;
                $institutions[$role->getType()] = [];
            }
            $institutions[$role->getType()][] = $authorization->institutionRelation;
        }
        foreach ($roles as $role) {
            $institutionAuthorizationOption = InstitutionAuthorizationOption::fromInstitutions($role, $institution, $institutions[$role->getType()]);
            $this->institutionOptions[$role->getType()] = $institutionAuthorizationOption;
        }
    }

    /**
     * @param Institution $institution
     * @param InstitutionAuthorization[]|null
     * @return InstitutionAuthorizationOptionMap
     */
    public static function fromInstitutionAuthorizations(Institution $institution, array $institutionAuthorizations): self
    {
        return new self($institution, $institutionAuthorizations);
    }

    /**
     * InstitutionAuthorizationOption
     * @param InstitutionRole $role
     * @return InstitutionAuthorizationOption
     */
    public function getAuthorizationOptionsByRole(InstitutionRole $role)
    {
        if (!isset($this->institutionOptions[$role->getType()])) {
            return InstitutionAuthorizationOption::getEmpty($role);
        }

        return $this->institutionOptions[$role->getType()];
    }
}
