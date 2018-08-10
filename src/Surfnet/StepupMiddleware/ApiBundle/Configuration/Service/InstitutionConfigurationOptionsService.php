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
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\InstitutionConfigurationOptionsRepository;

class InstitutionConfigurationOptionsService
{
    /**
     * @var InstitutionConfigurationOptionsRepository
     */
    private $repository;

    /**
     * @var int
     */
    private $numberOfTokensPerIdentity;

    /**
     * @param InstitutionConfigurationOptionsRepository $repository
     * @param int $numberOfTokensPerIdentity
     */
    public function __construct(
        InstitutionConfigurationOptionsRepository $repository,
        $numberOfTokensPerIdentity
    ) {
        $this->repository = $repository;
        $this->numberOfTokensPerIdentity = $numberOfTokensPerIdentity;
    }

    /**
     * @return InstitutionConfigurationOptions[]
     */
    public function findAllInstitutionConfigurationOptions()
    {
        return $this->repository->findAll();
    }

    /**
     * @param Institution $institution
     * @return InstitutionConfigurationOptions|null
     */
    public function findInstitutionConfigurationOptionsFor(Institution $institution)
    {
        return $this->repository->findConfigurationOptionsFor($institution);
    }

    /**
     * Retrieve the number of tokens an identity is allowed to register/vet for a given institution.
     *
     * When the DISABLED value is set on the institution (when no specific configuration was pushed) the application
     * default is returned.
     *
     * @param Institution $institution
     * @return int
     */
    public function getMaxNumberOfTokensFor(Institution $institution)
    {
        $configuration = $this->findInstitutionConfigurationOptionsFor($institution);

        if ($configuration !== null && $configuration->numberOfTokensPerIdentityOption->isEnabled()) {
            return $configuration->numberOfTokensPerIdentityOption->getNumberOfTokensPerIdentity();
        }

        // Return the application globally set default when no institution specific value was set
        return $this->numberOfTokensPerIdentity;
    }
}
