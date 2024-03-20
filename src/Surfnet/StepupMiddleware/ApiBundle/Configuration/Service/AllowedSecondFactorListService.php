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
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\AllowedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\AllowedSecondFactorRepository;

class AllowedSecondFactorListService
{
    public function __construct(
        private readonly AllowedSecondFactorRepository $allowedSecondFactorRepository,
    ) {
    }

    public function getAllowedSecondFactorListFor(Institution $institution): AllowedSecondFactorList
    {
        $allowedSecondFactors = array_map(
            fn(AllowedSecondFactor $allowedSecondFactor) => $allowedSecondFactor->secondFactorType,
            $this->allowedSecondFactorRepository->getAllowedSecondFactorsFor($institution),
        );

        return AllowedSecondFactorList::ofTypes($allowedSecondFactors);
    }

    /**
     * @return AllowedSecondFactorMap
     */
    public function getAllowedSecondFactorMap()
    {
        return AllowedSecondFactorMap::from($this->allowedSecondFactorRepository->findAll());
    }
}
