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
use DateTime as CoreDateTime;
use Mockery as m;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Entity\ConfigurableSettings;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\LocalePreferenceExpressedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\Stepup\Identity\Value\TimeFrame;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\BootstrapIdentityWithYubikeySecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ExpressLocalePreferenceCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveGssfPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhonePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveYubikeyPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\UpdateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VerifyEmailCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VetSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\IdentityCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTest;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\DateTimeHelper;

/**
 * @runTestsInSeparateProcesses
 */
class IdentityCommandHandlerTest extends CommandHandlerTest
{
    private static $window = 3600;

    protected function createCommandHandler(EventStoreInterface $eventStore, EventBusInterface $eventBus)
    {
        $aggregateFactory = new PublicConstructorAggregateFactory();

        return new IdentityCommandHandler(
            new IdentityRepository(
                new IdentityIdEnforcingEventStoreDecorator($eventStore),
                $eventBus,
                $aggregateFactory
            ),
            ConfigurableSettings::create(self::$window, ['nl_NL', 'en_GB'])
        );
    }

    /**
     * @test
     * @group command-handler
     * @runInSeparateProcess
     */
    public function an_identity_can_be_bootstrapped_with_a_yubikey_second_factor()
    {
        $command                  = new BootstrapIdentityWithYubikeySecondFactorCommand();
        $command->identityId      = 'ID-ID';
        $command->nameId          = 'N-ID';
        $command->institution     = 'Institution';
        $command->commonName      = 'Enrique';
        $command->email           = 'foo@domain.invalid';
        $command->preferredLocale = 'nl_NL';
        $command->secondFactorId  = 'SF-ID';
        $command->yubikeyPublicId = 'Y-ID';

        $identityId = new IdentityId($command->identityId);
        $this->scenario
            ->withAggregateId($command->identityId)
            ->when($command)
            ->then([
                new IdentityCreatedEvent(
                    $identityId,
                    new Institution('Institution'),
                    new NameId('N-ID'),
                    new CommonName($command->commonName),
                    new Email($command->email),
                    new Locale('nl_NL')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $identityId,
                    new NameId('N-ID'),
                    new Institution('Institution'),
                    new CommonName($command->commonName),
                    new Email($command->email),
                    new Locale('nl_NL'),
                    new SecondFactorId('SF-ID'),
                    new YubikeyPublicId('Y-ID')
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @runInSeparateProcess
     */
    public function a_yubikey_possession_can_be_proven()
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        m::mock('alias:Surfnet\StepupBundle\Security\OtpGenerator')
            ->shouldReceive('generate')->once()->andReturn('regcode');
        m::mock('alias:Surfnet\Stepup\Token\TokenGenerator')
            ->shouldReceive('generateNonce')->once()->andReturn('nonce');

        $id                = new IdentityId(self::uuid());
        $institution       = new Institution('A Corp.');
        $nameId            = new NameId(md5(__METHOD__));
        $email             = new Email('info@domain.invalid');
        $commonName        = new CommonName('Henk Westbroek');
        $preferredLocale   = new Locale('en_GB');
        $secFacId          = new SecondFactorId(self::uuid());
        $pubId             = new YubikeyPublicId('ccccvfeghijk');

        $command                  = new ProveYubikeyPossessionCommand();
        $command->identityId      = (string) $id;
        $command->secondFactorId  = (string) $secFacId;
        $command->yubikeyPublicId = (string) $pubId;

        $this->scenario
            ->withAggregateId($id)
            ->given([new IdentityCreatedEvent(
                $id,
                $institution,
                $nameId,
                $commonName,
                $email,
                $preferredLocale
            )])
            ->when($command)
            ->then([
                new YubikeyPossessionProvenEvent(
                    $id,
                    $institution,
                    $secFacId,
                    $pubId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    $commonName,
                    $email,
                    $preferredLocale
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function yubikey_possession_cannot_be_proven_twice()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'more than one token');

        $id                = new IdentityId(self::uuid());
        $institution       = new Institution('A Corp.');
        $nameId            = new NameId(md5(__METHOD__));
        $email             = new Email('info@domain.invalid');
        $commonName        = new CommonName('Henk Westbroek');
        $preferredLocale   = new Locale('en_GB');
        $secFacId1         = new SecondFactorId(self::uuid());
        $pubId1            = new YubikeyPublicId('ccccvfeghijk');

        $command                  = new ProveYubikeyPossessionCommand();
        $command->identityId      = (string) $id;
        $command->secondFactorId  = (string) $secFacId1;
        $command->yubikeyPublicId = (string) $pubId1;

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent(
                    $id,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $preferredLocale
                ),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $institution,
                    $secFacId1,
                    $pubId1,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    $commonName,
                    $email,
                    $preferredLocale
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @runInSeparateProcess
     */
    public function a_phone_possession_can_be_proven()
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        m::mock('alias:Surfnet\StepupBundle\Security\OtpGenerator')
            ->shouldReceive('generate')->once()->andReturn('regcode');
        m::mock('alias:Surfnet\Stepup\Token\TokenGenerator')
            ->shouldReceive('generateNonce')->once()->andReturn('nonce');

        $id                = new IdentityId(self::uuid());
        $institution       = new Institution('A Corp.');
        $nameId            = new NameId(md5(__METHOD__));
        $email             = new Email('info@domain.invalid');
        $commonName        = new CommonName('Henk Westbroek');
        $preferredLocale   = new Locale('en_GB');
        $secFacId          = new SecondFactorId(self::uuid());
        $phoneNumber       = new PhoneNumber('+31 (0) 612345678');

        $command                 = new ProvePhonePossessionCommand();
        $command->identityId     = (string) $id;
        $command->secondFactorId = (string) $secFacId;
        $command->phoneNumber    = (string) $phoneNumber;

        $this->scenario
            ->withAggregateId($id)
            ->given([new IdentityCreatedEvent(
                $id,
                $institution,
                $nameId,
                $commonName,
                $email,
                $preferredLocale
            )])
            ->when($command)
            ->then([
                new PhonePossessionProvenEvent(
                    $id,
                    $institution,
                    $secFacId,
                    $phoneNumber,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    $commonName,
                    $email,
                    $preferredLocale
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @runInSeparateProcess
     */
    public function a_gssf_possession_can_be_proven()
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        m::mock('alias:Surfnet\StepupBundle\Security\OtpGenerator')
            ->shouldReceive('generate')->once()->andReturn('regcode');

        $nonce = 'nonce';
        m::mock('alias:Surfnet\Stepup\Token\TokenGenerator')
            ->shouldReceive('generateNonce')->once()->andReturn($nonce);

        $identityId        = new IdentityId(self::uuid());
        $institution       = new Institution('Surfnet');
        $nameId            = new NameId(md5(__METHOD__));
        $email             = new Email('info@domain.invalid');
        $commonName        = new CommonName('Henk Westbroek');
        $preferredLocale   = new Locale('en_GB');
        $secondFactorId    = new SecondFactorId(self::uuid());
        $stepupProvider    = new StepupProvider('tiqr');
        $gssfId            = new GssfId('_' . md5('Surfnet'));

        $command                 = new ProveGssfPossessionCommand();
        $command->identityId     = (string) $identityId;
        $command->secondFactorId = (string) $secondFactorId;
        $command->stepupProvider = (string) $stepupProvider;
        $command->gssfId         = (string) $gssfId;

        $this->scenario
            ->withAggregateId($identityId)
            ->given([new IdentityCreatedEvent(
                $identityId,
                $institution,
                $nameId,
                $commonName,
                $email,
                $preferredLocale
            )])
            ->when($command)
            ->then([
                new GssfPossessionProvenEvent(
                    $identityId,
                    $institution,
                    $secondFactorId,
                    $stepupProvider,
                    $gssfId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    $nonce,
                    $commonName,
                    $email,
                    $preferredLocale
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function phone_possession_cannot_be_proven_twice()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'more than one token');

        $id                = new IdentityId(self::uuid());
        $institution       = new Institution('A Corp.');
        $nameId            = new NameId(md5(__METHOD__));
        $email             = new Email('info@domain.invalid');
        $commonName        = new CommonName('Henk Westbroek');
        $preferredLocale   = new Locale('en_GB');
        $secFacId1         = new SecondFactorId(self::uuid());
        $phoneNumber1      = new PhoneNumber('+31 (0) 612345678');

        $command                 = new ProvePhonePossessionCommand();
        $command->identityId     = (string) $id;
        $command->secondFactorId = (string) $secFacId1;
        $command->phoneNumber    = (string) $phoneNumber1;

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent(
                    $id,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $preferredLocale
                ),
                new PhonePossessionProvenEvent(
                    $id,
                    $institution,
                    $secFacId1,
                    $phoneNumber1,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    $commonName,
                    $email,
                    $preferredLocale
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function cannot_prove_possession_of_arbitrary_second_factor_type_twice()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'more than one token');

        $id                = new IdentityId(self::uuid());
        $institution       = new Institution('A Corp.');
        $nameId            = new NameId(md5(__METHOD__));
        $email             = new Email('info@domain.invalid');
        $commonName        = new CommonName('Henk Westbroek');
        $preferredLocale   = new Locale('en_GB');
        $secFacId1         = new SecondFactorId(self::uuid());
        $publicId          = new YubikeyPublicId('ccccvfeghijk');
        $phoneNumber       = new PhoneNumber('+31 (0) 676543210');

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId1;
        $command->phoneNumber = (string) $phoneNumber;

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent(
                    $id,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $preferredLocale
                ),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $institution,
                    $secFacId1,
                    $publicId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    $commonName,
                    $email,
                    $preferredLocale
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @runInSeparateProcess
     */
    public function an_unverified_second_factors_email_can_be_verified()
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        m::mock('alias:Surfnet\StepupBundle\Security\OtpGenerator')
            ->shouldReceive('generate')->once()->andReturn('regcode');

        $id                     = new IdentityId(self::uuid());
        $institution            = new Institution('A Corp.');
        $nameId                 = new NameId(md5(__METHOD__));
        $email                  = new Email('info@domain.invalid');
        $commonName             = new CommonName('Henk Westbroek');
        $preferredLocale        = new Locale('en_GB');
        $secondFactorId         = new SecondFactorId(self::uuid());
        $secondFactorIdentifier = new YubikeyPublicId('ccccvfeghijk');

        $command                    = new VerifyEmailCommand();
        $command->identityId        = (string) $id;
        $command->verificationNonce = 'nonce';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent(
                    $id,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $preferredLocale
                ),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $institution,
                    $secondFactorId,
                    $secondFactorIdentifier,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    $commonName,
                    $email,
                    $preferredLocale
                )
            ])
            ->when($command)
            ->then([
                new EmailVerifiedEvent(
                    $id,
                    $institution,
                    $secondFactorId,
                    new SecondFactorType('yubikey'),
                    $secondFactorIdentifier,
                    DateTime::now(),
                    'regcode',
                    $commonName,
                    $email,
                    $preferredLocale
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function a_verified_second_factors_email_cannot_be_verified()
    {
        $this->setExpectedException(
            'Surfnet\Stepup\Exception\DomainException',
            'Cannot verify second factor, no unverified second factor can be verified using the given nonce'
        );

        $id                     = new IdentityId(self::uuid());
        $institution            = new Institution('A Corp.');
        $nameId                 = new NameId(md5(__METHOD__));
        $email                  = new Email('info@domain.invalid');
        $commonName             = new CommonName('Henk Westbroek');
        $preferredLocale        = new Locale('en_GB');
        $secondFactorId         = new SecondFactorId(self::uuid());
        $secondFactorIdentifier = new YubikeyPublicId('ccccvfeghijk');

        $command = new VerifyEmailCommand();
        $command->identityId = (string) $id;
        $command->verificationNonce = 'nonce';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent(
                    $id,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $preferredLocale
                ),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $institution,
                    $secondFactorId,
                    $secondFactorIdentifier,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    $commonName,
                    $email,
                    $preferredLocale
                ),
                new EmailVerifiedEvent(
                    $id,
                    $institution,
                    $secondFactorId,
                    new SecondFactorType('yubikey'),
                    $secondFactorIdentifier,
                    DateTime::now(),
                    'regcode',
                    $commonName,
                    $email,
                    $preferredLocale
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function cannot_verify_an_email_after_the_verification_window_has_closed()
    {
        $this->setExpectedException(
            'Surfnet\Stepup\Exception\DomainException',
            'Cannot verify second factor, the verification window is closed.'
        );

        $id = new IdentityId(self::uuid());
        $secondFactorId = new SecondFactorId(self::uuid());
        $publicId = new YubikeyPublicId('ccccvfeghijk');
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email             = new Email('info@domain.invalid');
        $commonName        = new CommonName('Henk Westbroek');
        $preferredLocale   = new Locale('en_GB');

        $command = new VerifyEmailCommand();
        $command->identityId = (string) $id;
        $command->verificationNonce = 'nonce';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent(
                    $id,
                    $institution,
                    $nameId,
                    $commonName,
                    $email,
                    $preferredLocale
                ),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $institution,
                    $secondFactorId,
                    $publicId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        new DateTime(new CoreDateTime('-2 days'))
                    ),
                    'nonce',
                    $commonName,
                    $email,
                    $preferredLocale
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function it_can_create_a_new_identity()
    {
        $createCommand = new CreateIdentityCommand();
        $createCommand->UUID = '1';
        $createCommand->id = '2';
        $createCommand->institution = 'A Corp.';
        $createCommand->nameId = '3';
        $createCommand->preferredLocale = 'nl_NL';
        $createCommand->email = 'a@domain.invalid';
        $createCommand->commonName = 'foobar';

        $identityId                = new IdentityId($createCommand->id);
        $identityInstitution       = new Institution($createCommand->institution);
        $identityNameId            = new NameId($createCommand->nameId);
        $identityEmail             = new Email($createCommand->email);
        $identityCommonName        = new CommonName($createCommand->commonName);
        $identityPreferredLocale   = new Locale($createCommand->preferredLocale);

        $createdEvent = new IdentityCreatedEvent(
            $identityId,
            $identityInstitution,
            $identityNameId,
            $identityCommonName,
            $identityEmail,
            $identityPreferredLocale
        );

        $this->scenario
            ->given([])
            ->when($createCommand)
            ->then([$createdEvent]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_identity_can_be_updated()
    {
        $id                = new IdentityId('42');
        $institution       = new Institution('A Corp.');
        $email             = new Email('info@domain.invalid');
        $commonName        = new CommonName('Henk Westbroek');

        $createdEvent = new IdentityCreatedEvent(
            $id,
            $institution,
            new NameId('3'),
            $commonName,
            $email,
            new Locale('de_DE')
        );

        $updateCommand             = new UpdateIdentityCommand();
        $updateCommand->id         = $id->getValue();
        $updateCommand->email      = 'new-email@domain.invalid';
        $updateCommand->commonName = 'Henk Hendriksen';

        $this->scenario
            ->withAggregateId($id)
            ->given([$createdEvent])
            ->when($updateCommand)
            ->then([
                new IdentityRenamedEvent($id, $institution, new CommonName($updateCommand->commonName)),
                new IdentityEmailChangedEvent($id, $institution, new Email($updateCommand->email))
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function a_second_factor_can_be_vetted()
    {
        $command                         = new VetSecondFactorCommand();
        $command->authorityId            = 'AID';
        $command->identityId             = 'IID';
        $command->secondFactorId         = 'ISFID';
        $command->registrationCode       = 'REGCODE';
        $command->secondFactorType       = 'yubikey';
        $command->secondFactorIdentifier = 'ccccvfeghijk';
        $command->documentNumber         = 'NH9392';
        $command->identityVerified       = true;

        $authorityId                = new IdentityId($command->authorityId);
        $authorityNameId            = new NameId($this->uuid());
        $authorityInstitution       = new Institution('Wazoo');
        $authorityEmail             = new Email('info@domain.invalid');
        $authorityCommonName        = new CommonName('Henk Westbroek');

        $registrantId                = new IdentityId($command->identityId);
        $registrantInstitution       = new Institution('A Corp.');
        $registrantNameId            = new NameId('3');
        $registrantEmail             = new Email('reg@domain.invalid');
        $registrantCommonName        = new CommonName('Reginald Waterloo');
        $registrantSecFacId          = new SecondFactorId('ISFID');
        $registrantSecFacIdentifier  = new YubikeyPublicId('ccccvfeghijk');

        $this->scenario
            ->withAggregateId($authorityId)
            ->given([
                new IdentityCreatedEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityNameId,
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $authorityId,
                    $authorityNameId,
                    $authorityInstitution,
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                    new SecondFactorId($this->uuid()),
                    new YubikeyPublicId('ccccvkdowiej')
                )
            ])
            ->withAggregateId($registrantId)
            ->given([
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeyPossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    $registrantSecFacIdentifier,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB')
                ),
                new EmailVerifiedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $registrantSecFacIdentifier,
                    DateTime::now(),
                    'REGCODE',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB')
                ),
            ])
            ->when($command)
            ->then([
                new SecondFactorVettedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('ccccvfeghijk'),
                    new DocumentNumber('NH9392'),
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB')
                ),
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @expectedException \Surfnet\Stepup\Exception\DomainException
     * @expectedExceptionMessage Authority does not have the required LoA
     */
    public function a_second_factor_cannot_be_vetted_without_a_secure_enough_vetted_second_factor()
    {
        $command                         = new VetSecondFactorCommand();
        $command->authorityId            = 'AID';
        $command->identityId             = 'IID';
        $command->secondFactorId         = 'ISFID';
        $command->registrationCode       = 'REGCODE';
        $command->secondFactorType       = 'yubikey';
        $command->secondFactorIdentifier = 'ccccvfeghijk';
        $command->documentNumber         = 'NH9392';
        $command->identityVerified       = true;

        $authorityId                = new IdentityId($command->authorityId);
        $authorityInstitution       = new Institution('Wazoo');
        $authorityNameId            = new NameId($this->uuid());
        $authorityEmail             = new Email('info@domain.invalid');
        $authorityCommonName        = new CommonName('Henk Westbroek');
        $authorityPhoneSfId         = new SecondFactorId($this->uuid());
        $authorityPhoneNo           = new PhoneNumber('+31 (0) 612345678');

        $registrantId                = new IdentityId($command->identityId);
        $registrantInstitution       = new Institution('A Corp.');
        $registrantNameId            = new NameId('3');
        $registrantEmail             = new Email('reg@domain.invalid');
        $registrantCommonName        = new CommonName('Reginald Waterloo');
        $registrantSecFacId          = new SecondFactorId('ISFID');
        $registrantPubId             = new YubikeyPublicId('ccccvfeghijk');

        $this->scenario
            ->withAggregateId($authorityId)
            ->given([
                new IdentityCreatedEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityNameId,
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB')
                ),
                new PhonePossessionProvenEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityPhoneSfId,
                    $authorityPhoneNo,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB')
                ),
                new EmailVerifiedEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityPhoneSfId,
                    new SecondFactorType('sms'),
                    $authorityPhoneNo,
                    DateTime::now(),
                    'regcode',
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB')
                ),
                new SecondFactorVettedEvent(
                    $authorityId,
                    $authorityNameId,
                    $authorityInstitution,
                    $authorityPhoneSfId,
                    new SecondFactorType('sms'),
                    $authorityPhoneNo,
                    new DocumentNumber('NG-RB-81'),
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB')
                )
            ])
            ->withAggregateId($registrantId)
            ->given([
                new IdentityCreatedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantNameId,
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB')
                ),
                new YubikeyPossessionProvenEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    $registrantPubId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB')
                ),
                new EmailVerifiedEvent(
                    $registrantId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    $registrantPubId,
                    DateTime::now(),
                    'REGCODE',
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB')
                ),
            ])
            ->when($command)
            ->then([
                new SecondFactorVettedEvent(
                    $registrantId,
                    $registrantNameId,
                    $registrantInstitution,
                    $registrantSecFacId,
                    new SecondFactorType('yubikey'),
                    new YubikeyPublicId('ccccvfeghijk'),
                    new DocumentNumber('NH9392'),
                    $registrantCommonName,
                    $registrantEmail,
                    new Locale('en_GB')
                ),
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @runInSeparateProcess
     */
    public function an_identity_can_express_its_locale_preference()
    {
        $command                  = new ExpressLocalePreferenceCommand();
        $command->identityId      = $this->uuid();
        $command->preferredLocale = 'nl_NL';

        $identityId  = new IdentityId($command->identityId);
        $institution = new Institution('Institution');

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    new NameId('N-ID'),
                    new CommonName('Matti Vanhanen'),
                    new Email('m.vanhanen@domain.invalid'),
                    new Locale('en_GB')
                ),
            ])
            ->when($command)
            ->then([
                new LocalePreferenceExpressedEvent($identityId, $institution, new Locale('nl_NL')),
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @runInSeparateProcess
     * @expectedException Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\DomainException
     * @expectedExceptionMessage Given locale "fi_FI" is not an acceptable locale
     */
    public function an_identity_cannot_express_a_preference_for_an_unsupported_locale()
    {
        $command                  = new ExpressLocalePreferenceCommand();
        $command->identityId      = $this->uuid();
        $command->preferredLocale = 'fi_FI';

        $identityId  = new IdentityId($command->identityId);
        $institution = new Institution('Institution');

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    new NameId('N-ID'),
                    new CommonName('Matti Vanhanen'),
                    new Email('m.vanhanen@domain.invalid'),
                    new Locale('en_GB')
                ),
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @runInSeparateProcess
     */
    public function an_identity_can_express_its_locale_preference_more_than_one_time()
    {
        $command                  = new ExpressLocalePreferenceCommand();
        $command->identityId      = $this->uuid();
        $command->preferredLocale = 'nl_NL';

        $identityId  = new IdentityId($command->identityId);
        $institution = new Institution('Institution');

        $this->scenario
            ->withAggregateId($command->identityId)
            ->given([
                new IdentityCreatedEvent(
                    $identityId,
                    $institution,
                    new NameId('N-ID'),
                    new CommonName('Matti Vanhanen'),
                    new Email('m.vanhanen@domain.invalid'),
                    new Locale('en_GB')
                ),
            ])
            ->when($command)
            ->when($command)
            ->then([
                new LocalePreferenceExpressedEvent($identityId, $institution, new Locale('nl_NL')),
                new LocalePreferenceExpressedEvent($identityId, $institution, new Locale('nl_NL')),
            ]);
    }
}
