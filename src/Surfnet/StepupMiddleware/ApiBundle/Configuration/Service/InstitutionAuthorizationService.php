<?php

/**
 * Copyright 2016 SURFnet B.V.
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
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionAuthorization;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionAuthorizationRepository;

class InstitutionAuthorizationService
{
    /**
     * @var InstitutionAuthorizationRepository
     */
    private $repository;

    /**
     * @param InstitutionAuthorizationRepository $repository
     */
    public function __construct(
        InstitutionAuthorizationRepository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * @param Institution $institution
     * @param Institution $for
     * @return bool
     */
    public function couldInstitutionUseRaFor(Institution $institution, Institution $for)
    {
        $authorization = $this->findAuthorizationFor($institution);
        return $authorization->useRaOption->hasOptions() && $authorization->useRaOption->isOption($for->getInstitution());
    }

    /**
     * @param Institution $institution
     * @param Institution $for
     * @return bool
     */
    public function couldInstitutionUseRaaFor(Institution $institution, Institution $for)
    {
        $authorization = $this->findAuthorizationFor($institution);
        return $authorization->useRaaOption->hasOptions() && $authorization->useRaaOption->isOption($for->getInstitution());
    }

    /**
     * @param Institution $institution
     * @param Institution $for
     * @return bool
     */
    public function couldInstitutionSelectRaaFor(Institution $institution, Institution $for)
    {
        $authorization = $this->findAuthorizationFor($institution);
        return $authorization->selectRaaOption->hasOptions() && $authorization->selectRaaOption->isOption($for->getInstitution());
    }


    /**
     * @param Institution $institution
     * @return InstitutionAuthorization
     */
    private function findAuthorizationFor(Institution $institution)
    {
        return $this->repository->findAuthorizationForInstitution($institution);
    }

}
