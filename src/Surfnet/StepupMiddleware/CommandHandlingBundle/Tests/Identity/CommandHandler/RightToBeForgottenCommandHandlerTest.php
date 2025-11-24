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

use Broadway\CommandHandling\CommandHandler;
use Broadway\EventHandling\EventBus as EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventStore\EventStore as EventStoreInterface;
use Mockery as m;
use Mockery\Matcher\IsEqual;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Helper\UserDataFilterInterface;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaForInstitutionEvent;
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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as ConcreteIdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\SraaRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ForgetIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\RightToBeForgottenCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Service\SensitiveDataService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTest;

#[RunTestsInSeparateProcesses]
class RightToBeForgottenCommandHandlerTest extends CommandHandlerTest
{
    /** @var MockInterface */
    private MockInterface $apiIdentityRepository;

    /** @var MockInterface */
    private MockInterface $sensitiveDataService;

    /** @var MockInterface */
    private MockInterface $sraaRepository;

    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus,
    ): CommandHandler {
        $aggregateFactory = new PublicConstructorAggregateFactory();

        $this->apiIdentityRepository = m::mock(
            ConcreteIdentityRepository::class,
        );
        $this->sensitiveDataService = m::mock(SensitiveDataService::class);
        $this->sraaRepository = m::mock(SraaRepository::class);

        $logger = m::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();

        return new RightToBeForgottenCommandHandler(
            new IdentityRepository(
                new IdentityIdEnforcingEventStoreDecorator($eventStore),
                $eventBus,
                $aggregateFactory,
                m::mock(UserDataFilterInterface::class),
                $logger,
            ),
            $this->apiIdentityRepository,
            $this->sensitiveDataService,
            $this->sraaRepository,
        );
    }

    #[Test]
    #[Group('command-handler')]
    #[Group('sensitive-data')]
    public function an_identity_can_be_forgotten(): void
    {
        $identityId = new IdentityId('A');
        $institution = new Institution('Helsingin Yliopisto');
        $nameId = new NameId('urn:eeva-kuopio');
        $commonName = new CommonName('Eeva Kuopio');
        $email = new Email('e.kuopio@hy.fi');
        $locale = new Locale('fi_FI');

        $this->apiIdentityRepository
            ->shouldReceive('findOneByNameIdAndInstitution')
            ->once()
            ->with(new IsEqual($nameId), new IsEqual($institution))
            ->andReturn($this->createIdentity($identityId->getIdentityId()));

        $this->sensitiveDataService
            ->shouldReceive('forgetSensitiveData')
            ->once()
            ->with(new IsEqual($identityId));

        $this->sraaRepository->shouldReceive('contains')->once()->with(new IsEqual($nameId))->andReturn(false);

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
                    $locale,
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    $locale,
                    new SecondFactorId('SF-ID'),
                    new YubikeyPublicId('01900473'),
                ),
            ])
            ->when($command)
            ->then([
                new IdentityForgottenEvent($identityId, $institution),
            ]);
    }

    #[Test]
    #[Group('command-handler')]
    #[Group('sensitive-data')]
    public function an_identity_may_be_forgotten_twice(): void
    {
        $identityId = new IdentityId('A');
        $institution = new Institution('Helsingin Yliopisto');
        $nameId = new NameId('urn:eeva-kuopio');
        $commonName = new CommonName('Eeva Kuopio');
        $email = new Email('e.kuopio@hy.fi');
        $locale = new Locale('fi_FI');

        $this->apiIdentityRepository
            ->shouldReceive('findOneByNameIdAndInstitution')
            ->once()
            ->with(new IsEqual($nameId), new IsEqual($institution))
            ->andReturn($this->createIdentity($identityId->getIdentityId()));

        $this->sensitiveDataService
            ->shouldReceive('forgetSensitiveData')
            ->once()
            ->with(new IsEqual($identityId));

        $this->sraaRepository->shouldReceive('contains')->once()->with(new IsEqual($nameId))->andReturn(false);

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
                    $locale,
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    $locale,
                    new SecondFactorId('SF-ID'),
                    new YubikeyPublicId('01900473'),
                ),
                new IdentityForgottenEvent($identityId, $institution),
            ])
            ->when($command)
            ->then([
                new IdentityForgottenEvent($identityId, $institution),
            ]);
    }

    #[Test]
    #[Group('command-handler')]
    #[Group('sensitive-data')]
    public function an_ra_cannot_be_forgotten(): void
    {
        $this->expectExceptionMessage("Cannot forget an identity that is currently accredited as an RA(A)");
        $this->expectException(DomainException::class);

        $identityId = new IdentityId('A');
        $institution = new Institution('Helsingin Yliopisto');
        $nameId = new NameId('urn:eeva-kuopio');
        $commonName = new CommonName('Eeva Kuopio');
        $email = new Email('e.kuopio@hy.fi');
        $locale = new Locale('fi_FI');

        $this->apiIdentityRepository
            ->shouldReceive('findOneByNameIdAndInstitution')
            ->once()
            ->with(new IsEqual($nameId), new IsEqual($institution))
            ->andReturn($this->createIdentity($identityId->getIdentityId()));

        $this->sraaRepository->shouldReceive('contains')->once()->with(new IsEqual($nameId))->andReturn(false);

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
                    $locale,
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    $locale,
                    new SecondFactorId('SF-ID'),
                    new YubikeyPublicId('01900473'),
                ),
                new IdentityAccreditedAsRaEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA),
                    new Location('0x0392ff832'),
                    new ContactInformation('/dev/null'),
                ),
            ])
            ->when($command);
    }

    #[Test]
    #[Group('command-handler')]
    #[Group('sensitive-data')]
    public function an_raa_cannot_be_forgotten(): void
    {
        $this->expectExceptionMessage("Cannot forget an identity that is currently accredited as an RA(A)");
        $this->expectException(DomainException::class);

        $identityId = new IdentityId('A');
        $institution = new Institution('Helsingin Yliopisto');
        $nameId = new NameId('urn:eeva-kuopio');
        $commonName = new CommonName('Eeva Kuopio');
        $email = new Email('e.kuopio@hy.fi');
        $locale = new Locale('fi_FI');

        $this->apiIdentityRepository
            ->shouldReceive('findOneByNameIdAndInstitution')
            ->once()
            ->with(new IsEqual($nameId), new IsEqual($institution))
            ->andReturn($this->createIdentity($identityId->getIdentityId()));

        $this->sraaRepository->shouldReceive('contains')->once()->with(new IsEqual($nameId))->andReturn(false);

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
                    $locale,
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    $locale,
                    new SecondFactorId('SF-ID'),
                    new YubikeyPublicId('01900473'),
                ),
                new IdentityAccreditedAsRaForInstitutionEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA),
                    new Location('0x0392ff832'),
                    new ContactInformation('/dev/null'),
                    $institution,
                ),
            ])
            ->when($command);
    }

    #[Test]
    #[Group('command-handler')]
    #[Group('sensitive-data')]
    public function an_sraa_cannot_be_forgotten(): void
    {
        $this->expectExceptionMessage("Cannot forget an identity that is currently accredited as an SRAA");
        $this->expectException(RuntimeException::class);

        $identityId = new IdentityId('A');
        $institution = new Institution('Helsingin Yliopisto');
        $nameId = new NameId('urn:eeva-kuopio');
        $commonName = new CommonName('Eeva Kuopio');
        $email = new Email('e.kuopio@hy.fi');
        $locale = new Locale('fi_FI');

        $this->sraaRepository->shouldReceive('contains')->once()->with(new IsEqual($nameId))->andReturn(true);

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
                    $locale,
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    $locale,
                    new SecondFactorId('SF-ID'),
                    new YubikeyPublicId('01900473'),
                ),
            ])
            ->when($command);
    }

    private function createIdentity(string $identityId): Identity
    {
        $identity = new Identity();
        $identity->id = $identityId;
        return $identity;
    }
}
