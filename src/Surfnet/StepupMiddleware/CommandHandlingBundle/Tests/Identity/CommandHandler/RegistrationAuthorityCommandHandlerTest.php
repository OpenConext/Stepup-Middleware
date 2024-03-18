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
use Broadway\CommandHandling\CommandHandlerInterface;
use Broadway\EventHandling\EventBus as EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventStore\EventStore as EventStoreInterface;
use Mockery as m;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Configuration\EventSourcing\InstitutionConfigurationRepository;
use Surfnet\Stepup\Configuration\InstitutionConfiguration;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Helper\UserDataFilterInterface;
use Surfnet\Stepup\Identity\Event\AppointedAsRaaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityInformationAmendedForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedForInstitutionEvent;
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
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\AccreditIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\AmendRegistrationAuthorityInformationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\AppointRoleCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RetractRegistrationAuthorityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\RegistrationAuthorityCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\VettingTypeHintService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTest;

class RegistrationAuthorityCommandHandlerTest extends CommandHandlerTest
{

    /**
     * @var InstitutionConfigurationRepository|MockInterface
     */
    private $institutionConfigurationRepositoryMock;

    /**
     * @var InstitutionConfiguration|MockInterface
     */
    private $institutionConfiguration;

    /**
     * Create a command handler for the given scenario test case.
     *
     * @param EventStoreInterface $eventStore
     * @param EventBusInterface $eventBus
     *
     * @return CommandHandler
     */
    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus,
    ): CommandHandler {
        $aggregateFactory = new PublicConstructorAggregateFactory();

        $this->institutionConfigurationRepositoryMock = m::mock(InstitutionConfigurationRepository::class);
        $this->institutionConfiguration = m::mock(InstitutionConfiguration::class);

        $this->institutionConfigurationRepositoryMock
            ->shouldReceive('load')
            ->andReturn($this->institutionConfiguration);

        $logger = m::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();

        return new RegistrationAuthorityCommandHandler(
            new IdentityRepository(
                new IdentityIdEnforcingEventStoreDecorator($eventStore),
                $eventBus,
                $aggregateFactory,
                m::mock(UserDataFilterInterface::class),
                $logger,
            ),
            $this->institutionConfigurationRepositoryMock,
            m::mock(VettingTypeHintService::class),
        );
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_identity_cannot_be_accredited_for_another_institution_than_configured(): void
    {
        $this->expectExceptionMessage("An Identity may only be accredited by configured institutions");
        $this->expectException(DomainException::class);

        $command = new AccreditIdentityCommand();
        $command->identityId = static::uuid();
        $command->institution = 'Babelfish Inc.';
        $command->role = 'ra';
        $command->location = 'Somewhere behind you';
        $command->contactInformation = 'Call me Maybe';
        $command->raInstitution = 'RA institution';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution('A Different Institution than the Command holds');
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');

        $this->institutionConfiguration
            ->shouldReceive('isInstitutionAllowedToAccreditRoles')
            ->andReturn(false);

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                    $secondFactorId,
                    $secondFactorPublicId,
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_identity_cannot_be_accredited_when_it_does_not_have_a_vetted_second_factor(): void
    {
        $this->expectExceptionMessage(
            "An Identity must have at least one vetted second factor before it can be accredited",
        );
        $this->expectException(DomainException::class);
        $command = new AccreditIdentityCommand();
        $command->identityId = static::uuid();
        $command->institution = 'Babelfish Inc.';
        $command->role = 'ra';
        $command->location = 'Somewhere behind you';
        $command->contactInformation = 'Call me Maybe';
        $command->raInstitution = 'RA institution';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution($command->institution);
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');

        $this->institutionConfiguration
            ->shouldReceive('isInstitutionAllowedToAccreditRoles')
            ->andReturn(true);

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
            ])
            ->when($command);
    }


    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_identity_cannot_be_accredited_when_it_already_has_been_accredited(): void
    {
        $this->expectExceptionMessage("Cannot accredit Identity as it has already been accredited for institution");
        $this->expectException(DomainException::class);
        $command = new AccreditIdentityCommand();
        $command->identityId = static::uuid();
        $command->institution = 'Babelfish Inc.';
        $command->role = 'ra';
        $command->location = 'Somewhere behind you';
        $command->contactInformation = 'Call me Maybe';
        $command->raInstitution = 'Babelfish Inc.';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution($command->institution);
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');

        $this->institutionConfiguration
            ->shouldReceive('isInstitutionAllowedToAccreditRoles')
            ->andReturn(true);

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given(
                [
                    new IdentityCreatedEvent(
                        $identityId,
                        $institution,
                        $nameId,
                        $commonName,
                        $email,
                        new Locale('en_GB'),
                    ),
                    new YubikeySecondFactorBootstrappedEvent(
                        $identityId,
                        $nameId,
                        $institution,
                        $commonName,
                        $email,
                        new Locale('en_GB'),
                        $secondFactorId,
                        $secondFactorPublicId,
                    ),
                    new IdentityAccreditedAsRaEvent(
                        $identityId,
                        $nameId,
                        $institution,
                        new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA),
                        new Location('Somewhere behind you'),
                        new ContactInformation('Call me maybe'),
                    ),
                ],
            )
            ->when($command);
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_identity_cannot_be_accredited_with_an_invalid_role(): void
    {
        $this->expectException(RuntimeException::class);

        $command = new AccreditIdentityCommand();
        $command->identityId = static::uuid();
        $command->institution = 'Babelfish Inc.';
        $command->role = 'A role that does not exist';
        $command->location = 'Somewhere behind you';
        $command->contactInformation = 'Call me Maybe';
        $command->raInstitution = 'RA institution';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution($command->institution);
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given(
                [
                    new IdentityCreatedEvent(
                        $identityId,
                        $institution,
                        $nameId,
                        $commonName,
                        $email,
                        new Locale('en_GB'),
                    ),
                    new YubikeySecondFactorBootstrappedEvent(
                        $identityId,
                        $nameId,
                        $institution,
                        $commonName,
                        $email,
                        new Locale('en_GB'),
                        $secondFactorId,
                        $secondFactorPublicId,
                    ),
                ],
            )
            ->when($command);
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_identity_can_be_accredited_with_ra_role(): void
    {
        $command = new AccreditIdentityCommand();
        $command->identityId = static::uuid();
        $command->institution = 'RA institution';
        $command->role = 'ra';
        $command->location = 'Somewhere behind you';
        $command->contactInformation = 'Call me Maybe';
        $command->raInstitution = 'RA institution';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution($command->institution);
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');
        $raInstitution = new Institution($command->raInstitution);

        $this->institutionConfiguration
            ->shouldReceive('isInstitutionAllowedToAccreditRoles')
            ->andReturn(true);

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given(
                [
                    new IdentityCreatedEvent(
                        $identityId,
                        $institution,
                        $nameId,
                        $commonName,
                        $email,
                        new Locale('en_GB'),
                    ),
                    new YubikeySecondFactorBootstrappedEvent(
                        $identityId,
                        $nameId,
                        $institution,
                        $commonName,
                        $email,
                        new Locale('en_GB'),
                        $secondFactorId,
                        $secondFactorPublicId,
                    ),
                ],
            )
            ->when($command)
            ->then([
                new IdentityAccreditedAsRaForInstitutionEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA),
                    new Location($command->location),
                    new ContactInformation($command->contactInformation),
                    $raInstitution,
                ),
            ]);
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_identity_can_be_accredited_with_raa_role(): void
    {
        $command = new AccreditIdentityCommand();
        $command->identityId = static::uuid();
        $command->institution = 'Babelfish Inc.';
        $command->role = 'raa';
        $command->location = 'Somewhere behind you';
        $command->contactInformation = 'Call me Maybe';
        $command->raInstitution = 'RA institution';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution('Babelfish Inc.');
        $raInstitution = new Institution($command->raInstitution);
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');

        $this->institutionConfiguration
            ->shouldReceive('isInstitutionAllowedToAccreditRoles')
            ->andReturn(true);

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given(
                [
                    new IdentityCreatedEvent(
                        $identityId,
                        $institution,
                        $nameId,
                        $commonName,
                        $email,
                        new Locale('en_GB'),
                    ),
                    new YubikeySecondFactorBootstrappedEvent(
                        $identityId,
                        $nameId,
                        $institution,
                        $commonName,
                        $email,
                        new Locale('en_GB'),
                        $secondFactorId,
                        $secondFactorPublicId,
                    ),
                ],
            )
            ->when($command)
            ->then(
                [
                    new IdentityAccreditedAsRaaForInstitutionEvent(
                        $identityId,
                        $nameId,
                        $institution,
                        new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA),
                        new Location($command->location),
                        new ContactInformation($command->contactInformation),
                        $raInstitution,
                    ),
                ],
            );
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function a_registration_authoritys_information_can_be_amended(): void
    {
        $command = new AmendRegistrationAuthorityInformationCommand();
        $command->identityId = static::uuid();
        $command->location = 'New York';
        $command->contactInformation = '131 West 3rd Street, NY';
        $command->raInstitution = 'Ra institution';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution($command->raInstitution);
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given(
                [
                    new IdentityCreatedEvent(
                        $identityId,
                        $institution,
                        $nameId,
                        $commonName,
                        $email,
                        new Locale('en_GB'),
                    ),
                    new YubikeySecondFactorBootstrappedEvent(
                        $identityId,
                        $nameId,
                        $institution,
                        $commonName,
                        $email,
                        new Locale('en_GB'),
                        $secondFactorId,
                        $secondFactorPublicId,
                    ),
                    new IdentityAccreditedAsRaaForInstitutionEvent(
                        $identityId,
                        $nameId,
                        $institution,
                        new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA),
                        new Location('Somewhere behind you'),
                        new ContactInformation('Call me Maybe'),
                        $institution,
                    ),
                ],
            )
            ->when($command)
            ->then(
                [
                    new RegistrationAuthorityInformationAmendedForInstitutionEvent(
                        $identityId,
                        $institution,
                        $nameId,
                        new Location($command->location),
                        new ContactInformation($command->contactInformation),
                        $institution,
                    ),
                ],
            );
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_identitys_registration_authority_information_cannot_be_amended(): void
    {
        $this->expectExceptionMessage(
            "Cannot amend registration authority information: identity is not a registration authority",
        );
        $this->expectException(DomainException::class);

        $command = new AmendRegistrationAuthorityInformationCommand();
        $command->identityId = static::uuid();
        $command->location = 'New York';
        $command->contactInformation = '131 West 3rd Street, NY';
        $command->raInstitution = 'Ra institution';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution('Blue Note');
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given(
                [
                    new IdentityCreatedEvent(
                        $identityId,
                        $institution,
                        $nameId,
                        $commonName,
                        $email,
                        new Locale('en_GB'),
                    ),
                    new YubikeySecondFactorBootstrappedEvent(
                        $identityId,
                        $nameId,
                        $institution,
                        $commonName,
                        $email,
                        new Locale('en_GB'),
                        $secondFactorId,
                        $secondFactorPublicId,
                    ),
                ],
            )
            ->when($command);
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_identity_without_vetted_second_factor_may_not_be_accredited_as_ra(): void
    {
        $this->expectExceptionMessage(
            "An Identity must have at least one vetted second factor before it can be accredited",
        );
        $this->expectException(DomainException::class);

        $command = new AccreditIdentityCommand();
        $command->identityId = static::uuid();
        $command->institution = 'Babelfish Inc.';
        $command->role = 'ra';
        $command->location = 'somewhere';
        $command->contactInformation = 'Call me maybe';
        $command->raInstitution = 'RA institution';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution($command->institution);
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');

        $this->institutionConfiguration
            ->shouldReceive('isInstitutionAllowedToAccreditRoles')
            ->andReturn(true);

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_identity_with_a_vetted_second_factor_can_be_accredited_as_ra(): void
    {
        $command = new AccreditIdentityCommand();
        $command->identityId = static::uuid();
        $command->institution = 'Babelfish Inc.';
        $command->role = 'ra';
        $command->location = 'somewhere';
        $command->contactInformation = 'Call me maybe';
        $command->raInstitution = 'RA institution';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution($command->institution);
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');
        $raInstitution = new Institution($command->raInstitution);

        $this->institutionConfiguration
            ->shouldReceive('isInstitutionAllowedToAccreditRoles')
            ->andReturn(true);

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given(
                [
                    new IdentityCreatedEvent(
                        $identityId,
                        $institution,
                        $nameId,
                        $commonName,
                        $email,
                        new Locale('en_GB'),
                    ),
                    new YubikeySecondFactorBootstrappedEvent(
                        $identityId,
                        $nameId,
                        $institution,
                        $commonName,
                        $email,
                        new Locale('en_GB'),
                        $secondFactorId,
                        $secondFactorPublicId,
                    ),
                ],
            )
            ->when($command)
            ->then([
                new IdentityAccreditedAsRaForInstitutionEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA),
                    new Location($command->location),
                    new ContactInformation($command->contactInformation),
                    $raInstitution,
                ),
            ]);
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_identity_cannot_be_accredited_twice(): void
    {
        $this->expectExceptionMessage("Cannot accredit Identity as it has already been accredited for institution");
        $this->expectException(DomainException::class);

        $command = new AccreditIdentityCommand();
        $command->identityId = static::uuid();
        $command->institution = 'Babelfish Inc.';
        $command->role = 'ra';
        $command->location = 'somewhere';
        $command->contactInformation = 'Call me maybe';
        $command->raInstitution = 'Babelfish Inc.';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution($command->institution);
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');

        $this->institutionConfiguration
            ->shouldReceive('isInstitutionAllowedToAccreditRoles')
            ->andReturn(true);

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                    $secondFactorId,
                    $secondFactorPublicId,
                ),
                new IdentityAccreditedAsRaForInstitutionEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA),
                    new Location($command->location),
                    new ContactInformation($command->contactInformation),
                    $institution,
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_identity_cannot_be_accredited_as_sraa(): void
    {
        $this->expectException(RuntimeException::class);

        $command = new AccreditIdentityCommand();
        $command->identityId = static::uuid();
        $command->institution = 'Babelfish Inc.';
        $command->role = 'sraa';
        $command->location = 'somewhere';
        $command->contactInformation = 'Call me maybe';
        $command->raInstitution = 'Babelfish Inc.';


        $identityId = new IdentityId($command->identityId);
        $institution = new Institution($command->institution);
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                    $secondFactorId,
                    $secondFactorPublicId,
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_identity_that_is_accredited_as_raa_can_be_appointed_as_ra(): void
    {
        $command = new AppointRoleCommand();
        $command->identityId = static::uuid();
        $command->role = 'ra';
        $command->raInstitution = 'Babelfish Inc.';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution($command->raInstitution);
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');

        $this->institutionConfiguration
            ->shouldReceive('isInstitutionAllowedToAccreditRoles')
            ->andReturn(true);

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                    $secondFactorId,
                    $secondFactorPublicId,
                ),
                new IdentityAccreditedAsRaForInstitutionEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA),
                    new Location('somewhere'),
                    new ContactInformation('Call me maybe'),
                    $institution,
                ),
            ])
            ->when($command)
            ->then([
                new AppointedAsRaForInstitutionEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $institution,
                ),
            ]);
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_identity_that_is_accredited_as_ra_can_be_appointed_as_raa(): void
    {
        $command = new AppointRoleCommand();
        $command->identityId = static::uuid();
        $command->role = 'raa';
        $command->raInstitution = 'Ra institution';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution('Babelfish Inc.');
        $raInstitution = new Institution($command->raInstitution);
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');

        $this->institutionConfiguration
            ->shouldReceive('isInstitutionAllowedToAccreditRoles')
            ->andReturn(true);

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                    $secondFactorId,
                    $secondFactorPublicId,
                ),
                new IdentityAccreditedAsRaaForInstitutionEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA),
                    new Location('somewhere'),
                    new ContactInformation('Call me maybe'),
                    $raInstitution,
                ),
            ])
            ->when($command)
            ->then([
                new AppointedAsRaaForInstitutionEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $raInstitution,
                ),
            ]);
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_unaccredited_identity_cannot_be_appointed_a_registration_authority_role(): void
    {
        $this->expectExceptionMessage(
            "Cannot appoint as different RegistrationAuthorityRole: identity is not a registration authority",
        );
        $this->expectException(DomainException::class);

        $command = new AppointRoleCommand();
        $command->identityId = static::uuid();
        $command->role = 'raa';
        $command->raInstitution = 'RA institution';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution('Babelfish Inc.');
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');

        $this->institutionConfiguration
            ->shouldReceive('isInstitutionAllowedToAccreditRoles')
            ->andReturn(false);

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                    $secondFactorId,
                    $secondFactorPublicId,
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group                    command-handler
     * @group                    ra-command-handler
     */
    public function an_unaccredited_identity_cannot_have_its_registration_authority_retracted(): void
    {
        $this->expectExceptionMessage(
            "Cannot Retract Registration Authority as the Identity is not a registration authority",
        );
        $this->expectException(DomainException::class);

        $command = new RetractRegistrationAuthorityCommand();
        $command->identityId = static::uuid();
        $command->raInstitution = 'RA institution';

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution('Babelfish Inc.');
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                    $secondFactorId,
                    $secondFactorPublicId,
                ),
            ])
            ->when($command);
    }

    public function an_accredited_identity_can_retract_its_registration_authority(): void
    {
        $command = new RetractRegistrationAuthorityCommand();
        $command->identityId = static::uuid();

        $identityId = new IdentityId($command->identityId);
        $institution = new Institution('Babelfish Inc.');
        $raInstitution = new Institution('Ra institution');
        $nameId = new NameId(md5('someNameId'));
        $email = new Email('info@domain.invalid');
        $commonName = new CommonName('Henk Westbroek');
        $secondFactorId = new SecondFactorId(static::uuid());
        $secondFactorPublicId = new YubikeyPublicId('8329283834');

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    $commonName,
                    $email,
                    new Locale('en_GB'),
                    $secondFactorId,
                    $secondFactorPublicId,
                ),
                new IdentityAccreditedAsRaaForInstitutionEvent(
                    $identityId,
                    $nameId,
                    $institution,
                    new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RA),
                    new Location('somewhere'),
                    new ContactInformation('Call me maybe'),
                    $raInstitution,
                ),
            ])
            ->when($command)
            ->then([
                new RegistrationAuthorityRetractedForInstitutionEvent(
                    $identityId,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $raInstitution,
                ),
            ]);
    }
}
