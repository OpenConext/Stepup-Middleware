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
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionWithRaLocations;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionWithRaLocationsRepository;

class InstitutionWithRaLocationsService
{
    /**
     * @var InstitutionWithRaLocationsRepository
     */
    private $repository;

    public function __construct(InstitutionWithRaLocationsRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Institution $institution
     * @return boolean
     */
    public function institutionShowsRaLocations(Institution $institution)
    {
        return $this->repository->institutionShowsRaLocations($institution);
    }

    /**
     * @return InstitutionWithRaLocations[]
     */
    public function findAll()
    {
        return $this->repository->findAll();
    }
}
