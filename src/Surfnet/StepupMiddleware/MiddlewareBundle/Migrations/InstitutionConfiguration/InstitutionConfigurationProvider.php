<?php

/**
 * Copyright 2017 SURFnet bv
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Migrations\InstitutionConfiguration;

use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\ConfiguredInstitutionService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\RaLocationService;

final class InstitutionConfigurationProvider
{
    private ConfiguredInstitutionService $configuredInstitutionService;

    private InstitutionConfigurationOptionsService $institutionConfigurationOptionsService;

    private RaLocationService $raLocationService;

    /**
     * @param ConfiguredInstitutionService           $configuredInstitutionService
     * @param InstitutionConfigurationOptionsService $institutionConfigurationOptionsService
     * @param RaLocationService                      $raLocationService
     */
    public function __construct(
        ConfiguredInstitutionService $configuredInstitutionService,
        InstitutionConfigurationOptionsService $institutionConfigurationOptionsService,
        RaLocationService $raLocationService
    ) {
        $this->configuredInstitutionService = $configuredInstitutionService;
        $this->institutionConfigurationOptionsService = $institutionConfigurationOptionsService;
        $this->raLocationService = $raLocationService;
    }

    public function loadData(): InstitutionConfigurationState
    {
        $configuredInstitutions = $this->configuredInstitutionService->getAll();
        $institutionConfigurationOptions = $this->institutionConfigurationOptionsService
            ->findAllInstitutionConfigurationOptions();
        $raLocations = $this->raLocationService->getAllRaLocations();

        return InstitutionConfigurationState::load(
            $configuredInstitutions,
            $institutionConfigurationOptions,
            $raLocations
        );
    }
}
