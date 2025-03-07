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
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionAuthorizationRepository;

class InstitutionAuthorizationService
{
    public function __construct(private readonly InstitutionAuthorizationRepository $repository)
    {
    }

    /**
     * @return InstitutionAuthorizationOption
     */
    public function findAuthorizationsByRoleFor(
        Institution $institution,
        InstitutionRole $role,
    ): InstitutionAuthorizationOption {
        $authorizations = $this->repository->findAuthorizationOptionsForInstitutionByRole($institution, $role);

        $institutions = [];
        foreach ($authorizations as $authorization) {
            $institutions[] = $authorization->institutionRelation;
        }

        return InstitutionAuthorizationOption::fromInstitutions($role, $institution, $institutions);
    }

    /**
     * @return InstitutionAuthorizationOptionMap
     */
    public function findAuthorizationsFor(Institution $institution): InstitutionAuthorizationOptionMap
    {
        $authorizations = $this->repository->findAuthorizationOptionsForInstitution($institution);

        return InstitutionAuthorizationOptionMap::fromInstitutionAuthorizations($institution, $authorizations);
    }
}
