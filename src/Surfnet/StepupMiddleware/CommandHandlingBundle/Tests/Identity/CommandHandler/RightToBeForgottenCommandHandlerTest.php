<?php

/**
 * Copyright 2014 SURFnet bv
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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Identity\CommandHandler;

use Broadway\EventHandling\EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventStore\EventStoreInterface;
use Mockery as m;
use Mockery\MockInterface;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\ContactInformation;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\Location;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ForgetIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\RightToBeForgottenCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTest;

/**
 * @runTestsInSeparateProcesses
 */
class RightToBeForgottenCommandHandlerTest extends CommandHandlerTest
{
    /** @var MockInterface */
    private $apiIdentityRepository;

    /** @var MockInterface */
    private $sensitiveDataService;

    /** @var MockInterface */
    private $sraaRepository;

    protected function createCommandHandler(EventStoreInterface $eventStore, EventBusInterface $eventBus)
    {
        $aggregateFactory = new PublicConstructorAggregateFactory();

        $this->apiIdentityRepository = m::mock('Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository');
        $this->sensitiveDataService = m::mock('Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Service\SensitiveDataService');
        $this->sraaRepository = m::mock('Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SraaRepository');

        return new RightToBeForgottenCommandHandler(
            new IdentityRepository(
                new IdentityIdEnforcingEventStoreDecorator($eventStore),
                $eventBus,
                $aggregateFactory
            ),
            $this->apiIdentityRepository,
            $this->sensitiveDataService,
            $this->sraaRepository
        );
    }

    /**
     * @test
     * @group command-handler
     * @group sensitive-data
     */
    public function an_identity_can_be_forgotten()
    {
        $identityId  = new IdentityId('A');
        $institution = new Institution('Helsingin Yliopisto');
        $nameId      = new NameId('urn:eeva-kuopio');
        $commonName  = new CommonName('Eeva Kuopio');
        $email       = new Email('e.kuopio@hy.fi');
        $locale      = new Locale('fi_FI');;

        $this->apiIdentityRepository
            ->shouldReceive('findOneByNameIdAndInstitution')
            ->once()
            ->with(m::anyOf($nameId), m::anyOf($institution))
            ->andReturn((object) ['id' => $identityId->getIdentityId()]);

        $this->sensitiveDataService
            ->shouldReceive('forgetSensitiveData')
            ->once()
            ->with(m::anyOf($identityId));

        $this->sraaRepository->shouldReceive('contains')->once()->with(m::anyOf($nameId))->andReturn(false);

        $command = new ForgetIdentityCommand();
        $command->nameId = $nameId->getNameId();
        $command->institution = $institution->getInstitution();

        $this->scenario
            ->withAggregateId('A')
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $locale
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    $locale,
                    new SecondFactorId('SF-ID'),
                    new YubikeyPublicId('01900473')
                )
            ])
            ->when($command)
            ->then([
                new IdentityForgottenEvent($identityId, $institution),
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @group sensitive-data
     * @expectedException Surfnet\Stepup\Exception\DomainException
     * @expectedExceptionMessage Operation on this Identity is not allowed: it has been forgotten
     */
    public function an_identity_may_not_be_forgotten_twice()
    {
        $identityId  = new IdentityId('A');
        $institution = new Institution('Helsingin Yliopisto');
        $nameId      = new NameId('urn:eeva-kuopio');
        $commonName  = new CommonName('Eeva Kuopio');
        $email       = new Email('e.kuopio@hy.fi');
        $locale      = new Locale('fi_FI');;

        $this->apiIdentityRepository
            ->shouldReceive('findOneByNameIdAndInstitution')
            ->once()
            ->with(m::anyOf($nameId), m::anyOf($institution))
            ->andReturn((object) ['id' => $identityId->getIdentityId()]);

        $this->sensitiveDataService
            ->shouldReceive('forgetSensitiveData')
            ->once()
            ->with(m::anyOf($identityId));

        $this->sraaRepository->shouldReceive('contains')->once()->with(m::anyOf($nameId))->andReturn(false);

        $command = new ForgetIdentityCommand();
        $command->nameId = $nameId->getNameId();
        $command->institution = $institution->getInstitution();

        $this->scenario
            ->withAggregateId('A')
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $locale
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    $locale,
                    new SecondFactorId('SF-ID'),
                    new YubikeyPublicId('01900473')
                ),
                new IdentityForgottenEvent($identityId, $institution),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @group sensitive-data
     * @expectedException Surfnet\Stepup\Exception\DomainException
     * @expectedExceptionMessage Cannot forget an identity that is currently accredited as an RA(A)
     */
    public function an_ra_cannot_be_forgotten()
    {
        $identityId  = new IdentityId('A');
        $institution = new Institution('Helsingin Yliopisto');
        $nameId      = new NameId('urn:eeva-kuopio');
        $commonName  = new CommonName('Eeva Kuopio');
        $email       = new Email('e.kuopio@hy.fi');
        $locale      = new Locale('fi_FI');;

        $this->apiIdentityRepository
            ->shouldReceive('findOneByNameIdAndInstitution')
            ->once()
            ->with(m::anyOf($nameId), m::anyOf($institution))
            ->andReturn((object) ['id' => $identityId->getIdentityId()]);

        $this->sensitiveDataService
            ->shouldReceive('forgetSensitiveData')
            ->once()
            ->with(m::anyOf($identityId));

        $this->sraaRepository->shouldReceive('contains')->once()->with(m::anyOf($nameId))->andReturn(false);

        $command = new ForgetIdentityCommand();
        $command->nameId = $nameId->getNameId();
        $command->institution = $institution->getInstitution();

        $this->scenario
            ->withAggregateId('A')
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $locale
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    $locale,
                    new SecondFactorId('SF-ID'),
                    new YubikeyPublicId('01900473')
                ),
                new IdentityAccreditedAsRaEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA),
                    new Location('0x0392ff832'),
                    new ContactInformation('/dev/null'),
                    $institution
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @group sensitive-data
     * @expectedException Surfnet\Stepup\Exception\DomainException
     * @expectedExceptionMessage Cannot forget an identity that is currently accredited as an RA(A)
     */
    public function an_raa_cannot_be_forgotten()
    {
        $identityId  = new IdentityId('A');
        $institution = new Institution('Helsingin Yliopisto');
        $nameId      = new NameId('urn:eeva-kuopio');
        $commonName  = new CommonName('Eeva Kuopio');
        $email       = new Email('e.kuopio@hy.fi');
        $locale      = new Locale('fi_FI');;

        $this->apiIdentityRepository
            ->shouldReceive('findOneByNameIdAndInstitution')
            ->once()
            ->with(m::anyOf($nameId), m::anyOf($institution))
            ->andReturn((object) ['id' => $identityId->getIdentityId()]);

        $this->sensitiveDataService
            ->shouldReceive('forgetSensitiveData')
            ->once()
            ->with(m::anyOf($identityId));

        $this->sraaRepository->shouldReceive('contains')->once()->with(m::anyOf($nameId))->andReturn(false);

        $command = new ForgetIdentityCommand();
        $command->nameId = $nameId->getNameId();
        $command->institution = $institution->getInstitution();

        $this->scenario
            ->withAggregateId('A')
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $locale
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    $locale,
                    new SecondFactorId('SF-ID'),
                    new YubikeyPublicId('01900473')
                ),
                new IdentityAccreditedAsRaEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA),
                    new Location('0x0392ff832'),
                    new ContactInformation('/dev/null'),
                    $institution
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @group sensitive-data
     * @expectedException Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\RuntimeException
     * @expectedExceptionMessage Cannot forget an identity that is currently accredited as an SRAA
     */
    public function an_sraa_cannae_be_forgotten()
    {
        $identityId  = new IdentityId('A');
        $institution = new Institution('Helsingin Yliopisto');
        $nameId      = new NameId('urn:eeva-kuopio');
        $commonName  = new CommonName('Eeva Kuopio');
        $email       = new Email('e.kuopio@hy.fi');
        $locale      = new Locale('fi_FI');;

        $this->apiIdentityRepository
            ->shouldReceive('findOneByNameIdAndInstitution')
            ->once()
            ->with(m::anyOf($nameId), m::anyOf($institution))
            ->andReturn((object) ['id' => $identityId->getIdentityId()]);

        $this->sensitiveDataService
            ->shouldReceive('forgetSensitiveData')
            ->once()
            ->with(m::anyOf($identityId));

        $this->sraaRepository->shouldReceive('contains')->once()->with(m::anyOf($nameId))->andReturn(true);

        $command = new ForgetIdentityCommand();
        $command->nameId = $nameId->getNameId();
        $command->institution = $institution->getInstitution();

        $this->scenario
            ->withAggregateId('A')
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $locale
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    $locale,
                    new SecondFactorId('SF-ID'),
                    new YubikeyPublicId('01900473')
                )
            ])
            ->when($command);
    }
}
