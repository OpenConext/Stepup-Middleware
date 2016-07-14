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
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Event\InstitutionsAddedToWhitelistEvent;
use Surfnet\Stepup\Identity\Event\WhitelistCreatedEvent;
use Surfnet\Stepup\Identity\Event\WhitelistReplacedEvent;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\EventListener\WhitelistEventListener;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\ConfiguredInstitutionRepository;

class WhitelistEventListenerTest extends TestCase
{
    /**
     * @test
     * @group event-listener
     * @group institution-configuration
     */
    public function institution_configurations_are_created_when_a_whitelist_was_created_containing_non_configured_institutions()
    {
        $firstInstitutionName  = 'First institution';
        $secondInstitutionName = 'Second institution';

        $whitelistCreatedEvent = new WhitelistCreatedEvent(new InstitutionCollection([
            new Institution($firstInstitutionName),
            new Institution($secondInstitutionName),
        ]));

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->andReturn(false);

        $firstExpectedInstitution = new ConfigurationInstitution($firstInstitutionName);
        $secondExpectedInstitution = new ConfigurationInstitution($secondInstitutionName);

        $institutionConfigurationCreationServiceMock = Mockery::mock(InstitutionConfigurationCreationService::class);
        $institutionConfigurationCreationServiceMock->shouldReceive('createConfigurationFor')
            ->once()
            ->andReturn(Mockery::mustBe($firstExpectedInstitution));
        $institutionConfigurationCreationServiceMock->shouldReceive('createConfigurationFor')
            ->once()
            ->andReturn(Mockery::mustBe($secondExpectedInstitution));

        $whitelistEventListener = new WhitelistEventListener($repositoryMock, $institutionConfigurationCreationServiceMock);
        $whitelistEventListener->applyWhitelistCreatedEvent($whitelistCreatedEvent);
    }

    /**
     * @test
     * @group event-listener
     * @group institution-configuration
     */
    public function no_institution_configuration_is_created_for_an_already_configured_institution_when_a_whitelist_was_created()
    {
        $alreadyPresentInstitution = 'Already present';
        $newInstitution            = 'New';

        $whitelistCreatedEvent = new WhitelistCreatedEvent(new InstitutionCollection([
            new Institution($alreadyPresentInstitution),
            new Institution($newInstitution),
        ]));

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(true);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(false);

        $expectedInstitution = new ConfigurationInstitution($newInstitution);

        $institutionConfigurationCreationServiceMock = Mockery::mock(InstitutionConfigurationCreationService::class);
        $institutionConfigurationCreationServiceMock->shouldReceive('createConfigurationFor')
            ->once()
            ->andReturn(Mockery::mustBe($expectedInstitution));

        $whitelistEventListener = new WhitelistEventListener($repositoryMock, $institutionConfigurationCreationServiceMock);
        $whitelistEventListener->applyWhitelistCreatedEvent($whitelistCreatedEvent);
    }

    /**
     * @test
     * @group event-listener
     */
    public function institution_configurations_are_created_when_a_whitelist_was_replaced_containing_non_configured_institutions()
    {
        $firstInstitutionName  = 'First institution';
        $secondInstitutionName = 'Second institution';

        $whitelistReplacedEvent = new WhitelistReplacedEvent(new InstitutionCollection([
            new Institution($firstInstitutionName),
            new Institution($secondInstitutionName),
        ]));

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->andReturn(false);

        $firstExpectedInstitution  = new ConfigurationInstitution($firstInstitutionName);
        $secondExpectedInstitution = new ConfigurationInstitution($secondInstitutionName);

        $institutionConfigurationCreationServiceMock = Mockery::mock(InstitutionConfigurationCreationService::class);
        $institutionConfigurationCreationServiceMock->shouldReceive('createConfigurationFor')
            ->once()
            ->andReturn(Mockery::mustBe($firstExpectedInstitution));
        $institutionConfigurationCreationServiceMock->shouldReceive('createConfigurationFor')
            ->once()
            ->andReturn(Mockery::mustBe($secondExpectedInstitution));

        $whitelistEventListener = new WhitelistEventListener($repositoryMock, $institutionConfigurationCreationServiceMock);
        $whitelistEventListener->applyWhitelistReplacedEvent($whitelistReplacedEvent);
    }

    /**
     * @test
     * @group event-listener
     * @group institution-configuration
     */
    public function no_institution_configuration_is_created_for_an_already_configured_institution_when_a_whitelist_was_replaced()
    {
        $alreadyPresentInstitution = 'Already present';
        $newInstitution            = 'New';

        $whitelistCreatedEvent = new WhitelistReplacedEvent(new InstitutionCollection([
            new Institution($alreadyPresentInstitution),
            new Institution($newInstitution),
        ]));

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(true);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(false);

        $expectedInstitution = new ConfigurationInstitution($newInstitution);

        $institutionConfigurationCreationServiceMock = Mockery::mock(InstitutionConfigurationCreationService::class);
        $institutionConfigurationCreationServiceMock->shouldReceive('createConfigurationFor')
            ->once()
            ->andReturn(Mockery::mustBe($expectedInstitution));

        $whitelistEventListener = new WhitelistEventListener($repositoryMock, $institutionConfigurationCreationServiceMock);
        $whitelistEventListener->applyWhitelistReplacedEvent($whitelistCreatedEvent);
    }

    /**
     * @test
     * @group event-listener
     * @group institution-configuration
     */
    public function institution_configurations_are_created_when_non_configured_institutions_are_added_to_the_whitelist()
    {
        $firstInstitutionName  = 'First institution';
        $secondInstitutionName = 'Second institution';

        $institutionsAddedToWhitelistEvent = new InstitutionsAddedToWhitelistEvent(new InstitutionCollection([
            new Institution($firstInstitutionName),
            new Institution($secondInstitutionName),
        ]));

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->andReturn(false);

        $firstExpectedInstitution = new ConfigurationInstitution($firstInstitutionName);
        $secondExpectedInstitution = new ConfigurationInstitution($secondInstitutionName);

        $institutionConfigurationCreationServiceMock = Mockery::mock(InstitutionConfigurationCreationService::class);
        $institutionConfigurationCreationServiceMock->shouldReceive('createConfigurationFor')
            ->once()
            ->andReturn(Mockery::mustBe($firstExpectedInstitution));

        $institutionConfigurationCreationServiceMock->shouldReceive('createConfigurationFor')
            ->once()
            ->andReturn(Mockery::mustBe($secondExpectedInstitution));

        $whitelistEventListener = new WhitelistEventListener($repositoryMock, $institutionConfigurationCreationServiceMock);
        $whitelistEventListener->applyInstitutionsAddedToWhitelistEvent($institutionsAddedToWhitelistEvent);
    }

    /**
     * @test
     * @group event-listener
     * @group institution-configuration
     */
    public function no_institution_configuration_is_created_for_an_already_configured_institution_when_institutions_are_added_to_a_whitelist()
    {
        $alreadyPresentInstitution = 'Already present';
        $newInstitution            = 'New';

        $whitelistCreatedEvent = new InstitutionsAddedToWhitelistEvent(new InstitutionCollection([
            new Institution($alreadyPresentInstitution),
            new Institution($newInstitution),
        ]));

        $repositoryMock = Mockery::mock(ConfiguredInstitutionRepository::class);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(true);
        $repositoryMock->shouldReceive('hasConfigurationFor')
            ->once()
            ->andReturn(false);

        $expectedInstitution = new ConfigurationInstitution($newInstitution);

        $institutionConfigurationCreationServiceMock = Mockery::mock(InstitutionConfigurationCreationService::class);
        $institutionConfigurationCreationServiceMock->shouldReceive('createConfigurationFor')
            ->once()
            ->andReturn(Mockery::mustBe($expectedInstitution));

        $whitelistEventListener = new WhitelistEventListener($repositoryMock, $institutionConfigurationCreationServiceMock);
        $whitelistEventListener->applyInstitutionsAddedToWhitelistEvent($whitelistCreatedEvent);
    }
}
