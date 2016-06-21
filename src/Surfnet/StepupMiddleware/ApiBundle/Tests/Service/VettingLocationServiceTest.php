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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Service;

use Mockery as m;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\ContactInformation as ConfigurationContactInformation;
use Surfnet\Stepup\Configuration\Value\Institution as ConfigurationInstitution;
use Surfnet\Stepup\Configuration\Value\Location as ConfigurationLocation;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\ContactInformation;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\Institution as IdentityInstitution;
use Surfnet\Stepup\Identity\Value\Location;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\RaLocation;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\AuthorityRole;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;
use Surfnet\StepupMiddleware\ApiBundle\Service\VettingLocationService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Dto\VettingLocation;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Value\Institution;

class VettingLocationServiceTest extends TestCase
{
    /**
     * @test
     * @group api-bundle
     * @group vetting
     */
    public function vetting_locations_can_be_determined_for_institution_with_personal_ra_details()
    {
        $expectedRaName                = 'RA';
        $expectedRaLocation            = 'Personal location';
        $expectedRaContactInformation  = 'RA contact information';
        $expectedRaaName               = 'RAA';
        $expectedRaaLocation           = 'Another personal location';
        $expectedRaaContactInformation = 'RAA contact information';

        $institutionValue         = 'institution.test';
        $configurationInstitution = new ConfigurationInstitution($institutionValue);
        $identityInstitution      = new IdentityInstitution($institutionValue);

        $institutionsWithPersonalRaDetailsService = m::mock(
            'Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionWithPersonalRaDetailsService'
        );
        $raLocationsService                       = m::mock(
            'Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\RaLocationService'
        );
        $raListingService                         = m::mock(
            '\Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaListingService'
        );

        $institutionsWithPersonalRaDetailsService
            ->shouldReceive('institutionHasPersonalRaDetails')
            ->with(
                m::on(
                    function (ConfigurationInstitution $actualInstitution) use ($configurationInstitution) {
                        return $actualInstitution->equals($configurationInstitution);
                    }
                )
            )
            ->andReturn(true);

        $raListingService
            ->shouldReceive('listRegistrationAuthoritiesFor')
            ->with(
                m::on(
                    function (IdentityInstitution $actualInstitution) use ($identityInstitution) {
                        return $actualInstitution->equals($identityInstitution);
                    }
                )
            )
            ->andReturn(
                [
                    RegistrationAuthorityCredentials::fromRaListing(
                        RaListing::create(
                            'some-identity-id',
                            $identityInstitution,
                            new CommonName($expectedRaName),
                            new Email('ra@institution.test'),
                            AuthorityRole::ra(),
                            new Location($expectedRaLocation),
                            new ContactInformation($expectedRaContactInformation)
                        )
                    ),
                    RegistrationAuthorityCredentials::fromRaListing(
                        RaListing::create(
                            'some-identity-id',
                            $identityInstitution,
                            new CommonName($expectedRaaName),
                            new Email('raa@institution.test'),
                            AuthorityRole::raa(),
                            new Location($expectedRaaLocation),
                            new ContactInformation($expectedRaaContactInformation)
                        )
                    ),
                ]
            );

        $expectedVettingLocations = [
            new VettingLocation($expectedRaName, $expectedRaLocation, $expectedRaContactInformation),
            new VettingLocation($expectedRaaName, $expectedRaaLocation, $expectedRaaContactInformation),
        ];

        $vettingLocationService = new VettingLocationService(
            $institutionsWithPersonalRaDetailsService,
            $raLocationsService,
            $raListingService
        );

        $actualVettingLocations = $vettingLocationService->getVettingLocationsFor(new Institution($institutionValue));

        $this->assertEquals($expectedVettingLocations, $actualVettingLocations);
    }

    /**
     * @test
     * @group api-bundle
     * @group vetting
     */
    public function vetting_locations_can_be_determined_for_institution_with_ra_locations()
    {
        $expectedRaName                = 'RA Service Desk';
        $expectedRaLocation            = 'On site';
        $expectedRaContactInformation  = 'RA contact information';
        $expectedRaaName               = 'RAA Service Desk';
        $expectedRaaLocation           = 'On another site';
        $expectedRaaContactInformation = 'RAA contact information';

        $institutionValue         = 'institution.test';
        $configurationInstitution = new ConfigurationInstitution($institutionValue);

        $institutionsWithPersonalRaDetailsService = m::mock(
            'Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionWithPersonalRaDetailsService'
        );
        $raLocationsService                       = m::mock(
            'Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\RaLocationService'
        );
        $raListingService                         = m::mock(
            '\Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaListingService'
        );

        $institutionsWithPersonalRaDetailsService
            ->shouldReceive('institutionHasPersonalRaDetails')
            ->with(
                m::on(
                    function (ConfigurationInstitution $actualInstitution) use ($configurationInstitution) {
                        return $actualInstitution->equals($configurationInstitution);
                    }
                )
            )
            ->andReturn(false);

        $raLocationsService
            ->shouldReceive('listRaLocationsFor')
            ->with(
                m::on(
                    function (ConfigurationInstitution $actualInstitution) use ($configurationInstitution) {
                        return $actualInstitution->equals($configurationInstitution);
                    }
                )
            )
            ->andReturn(
                [
                    RaLocation::create(
                        'some-id',
                        $configurationInstitution,
                        new RaLocationName($expectedRaName),
                        new ConfigurationLocation($expectedRaLocation),
                        new ConfigurationContactInformation($expectedRaContactInformation)
                    ),
                    RaLocation::create(
                        'some-id',
                        $configurationInstitution,
                        new RaLocationName($expectedRaaName),
                        new ConfigurationLocation($expectedRaaLocation),
                        new ConfigurationContactInformation($expectedRaaContactInformation)
                    ),
                ]
            );

        $expectedVettingLocations = [
            new VettingLocation($expectedRaName, $expectedRaLocation, $expectedRaContactInformation),
            new VettingLocation($expectedRaaName, $expectedRaaLocation, $expectedRaaContactInformation),
        ];

        $vettingLocationService = new VettingLocationService(
            $institutionsWithPersonalRaDetailsService,
            $raLocationsService,
            $raListingService
        );

        $actualVettingLocations = $vettingLocationService->getVettingLocationsFor(new Institution($institutionValue));

        $this->assertEquals($expectedVettingLocations, $actualVettingLocations);
    }
}
