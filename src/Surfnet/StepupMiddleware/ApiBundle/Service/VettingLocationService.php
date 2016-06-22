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

namespace Surfnet\StepupMiddleware\ApiBundle\Service;

use Surfnet\Stepup\Configuration\Value\Institution as ConfigurationInstitution;
use Surfnet\Stepup\Identity\Value\Institution as IdentityInstitution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\RaLocation;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionWithRaLocationsService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\RaLocationService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaListingService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Service\VettingLocationService as VettingLocationServiceInterface;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Dto\VettingLocation;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Value\Institution;

final class VettingLocationService implements VettingLocationServiceInterface
{
    /**
     * @var InstitutionWithRaLocationsService
     */
    private $institutionWithRaLocations;

    /**
     * @var RaLocationService
     */
    private $raLocationService;

    /**
     * @var RaListingService
     */
    private $raListingService;

    public function __construct(
        InstitutionWithRaLocationsService $institutionWithRaLocationsService,
        RaLocationService $raLocationService,
        RaListingService $raListingService
    ) {
        $this->institutionWithRaLocations = $institutionWithRaLocationsService;
        $this->raLocationService          = $raLocationService;
        $this->raListingService           = $raListingService;
    }

    /**
     * @param Institution $institution
     * @return VettingLocation[]
     */
    public function getVettingLocationsFor(Institution $institution)
    {
        $configurationInstitution = new ConfigurationInstitution($institution->getInstitution());

        if ($this->institutionWithRaLocations->institutionShowsRaLocations($configurationInstitution)) {
            return array_map(
                function (RaLocation $raLocation) {
                    return new VettingLocation(
                        $raLocation->name->getRaLocationName(),
                        $raLocation->location->getLocation(),
                        $raLocation->contactInformation->getContactInformation()
                    );
                },
                $this->raLocationService->listRaLocationsFor($configurationInstitution)
            );
        }

        $identityInstitution = new IdentityInstitution($institution->getInstitution());

        return array_map(
            function (RegistrationAuthorityCredentials $credentials) {
                return new VettingLocation(
                    $credentials->getCommonName()->getCommonName(),
                    $credentials->getLocation()->getLocation(),
                    $credentials->getContactInformation()->getContactInformation()
                );
            },
            $this->raListingService->listRegistrationAuthoritiesFor($identityInstitution)
        );
    }
}
