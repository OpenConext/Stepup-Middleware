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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Configuration\EventListener;

use Mockery;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Api\InstitutionConfigurationCreationService;
use Surfnet\Stepup\Configuration\Value\Institution as ConfigurationInstitution;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\EventListener\IdentityEventListener;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\ConfiguredInstitutionRepository;

class IdentityEventListenerTest extends TestCase
{
    /**
     * @test
     * @group event-listener
     * @group institution-configuration
     */
    public function an_institution_configuration_command_is_created_when_an_identity_was_created_with_a_non_configured_institution()
    {
        $identityCreatedEvent = new IdentityCreatedEvent(
            new IdentityId('Id'),
            new Institution('Institution'),
            new NameId('Name Id'),
            new CommonName('Common name'),
            new Email('test@email.test'),
            new Locale('Locale')
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->andReturn(false);

        $expectedInstitution = new ConfigurationInstitution(
            $identityCreatedEvent->identityInstitution->getInstitution()
        );

        $institutionConfigurationCreationServiceMock = Mockery::mock(InstitutionConfigurationCreationService::class);
        $institutionConfigurationCreationServiceMock->shouldReceive('createConfigurationFor')
            ->once()
            ->with(Mockery::mustBe($expectedInstitution));

        $identityEventListener = new IdentityEventListener($repositoryMock, $institutionConfigurationCreationServiceMock);
        $identityEventListener->applyIdentityCreatedEvent($identityCreatedEvent);
    }

    /**
     * @test
     * @group event-listener
     * @group institution-configuration
     */
    public function no_institution_configuration_command_is_created_when_an_identity_was_created_with_an_already_configured_institution()
    {
        $identityCreatedEvent = new IdentityCreatedEvent(
            new IdentityId('Id'),
            new Institution('Institution'),
            new NameId('Name Id'),
            new CommonName('Common name'),
            new Email('test@email.test'),
            new Locale('Locale')
        );

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->andReturn(true);

        $institutionConfigurationCreationServiceMock = Mockery::mock(InstitutionConfigurationCreationService::class);
        $institutionConfigurationCreationServiceMock->shouldNotReceive('createConfigurationFor');

        $identityEventListener = new IdentityEventListener($repositoryMock, $institutionConfigurationCreationServiceMock);
        $identityEventListener->applyIdentityCreatedEvent($identityCreatedEvent);
    }
}
