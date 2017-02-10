<?php

/**
 * Copyright 2017 SURFnet B.V.
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

use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorMap;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\AllowedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\ConfiguredInstitutionRepository;

final class AllowedSecondFactorListService
{
    /**
     * @var AllowedSecondFactorRepository
     */
    private $allowedSecondFactorRepository;
    /**
     * @var ConfiguredInstitutionRepository
     */
    private $configuredInstitutionRepository;

    public function __construct(
        AllowedSecondFactorRepository $allowedSecondFactoryRepository,
        ConfiguredInstitutionRepository $configuredInstitutionRepository
    ) {
        $this->allowedSecondFactorRepository   = $allowedSecondFactoryRepository;
        $this->configuredInstitutionRepository = $configuredInstitutionRepository;
    }

    /**
     * @return AllowedSecondFactorList[]
     */
    public function getAllowedSecondFactorListsPerInstitution()
    {
        $allowedSecondFactors = $this->allowedSecondFactorRepository->findAll();

        $mappedAllowedSecondFactors = [];
        foreach ($allowedSecondFactors as $allowedSecondFactor) {
            $institution = $allowedSecondFactor->institution->getInstitution();
            $mappedAllowedSecondFactors[$institution][] = $allowedSecondFactor->secondFactorType;
        }

        $configuredInstitutions = $this->configuredInstitutionRepository->findAll();

        $mappedAllowedSecondFactorLists = [];
        foreach ($configuredInstitutions as $configuredInstitution) {
            $institution = $configuredInstitution->institution->getInstitution();

            if (isset($mappedAllowedSecondFactors[$institution])) {
                $mappedAllowedSecondFactorLists[$institution] =
                    AllowedSecondFactorList::ofTypes($mappedAllowedSecondFactors[$institution]);
            } else {
                $mappedAllowedSecondFactorLists[$institution] = AllowedSecondFactorList::blank();
            }
        }

        return $mappedAllowedSecondFactorLists;
    }
}