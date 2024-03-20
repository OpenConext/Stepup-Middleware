<?php

/**
 * Copyright 2022 SURFnet bv
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
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\Configuration\Value\SelfAssertedTokensOption;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Helper\RecoveryTokenSecretHelper;
use Surfnet\Stepup\Helper\SecondFactorProvePossessionHelper;
use Surfnet\Stepup\Helper\UserDataFilterInterface;
use Surfnet\Stepup\Identity\Entity\ConfigurableSettings;
use Surfnet\Stepup\Identity\Event\CompliedWithRecoveryCodeRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\PhoneRecoveryTokenPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\RecoveryTokenRevokedEvent;
use Surfnet\Stepup\Identity\Event\SafeStoreSecretRecoveryTokenPossessionPromisedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\HashedSecret;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\OnPremiseVettingType;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use Surfnet\Stepup\Identity\Value\SafeStore;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SelfAssertedRegistrationVettingType;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\Stepup\Identity\Value\TimeFrame;
use Surfnet\Stepup\Identity\Value\UnhashedSecret;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\AllowedSecondFactorListService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as IdentityProjectionRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\PromiseSafeStoreSecretTokenPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhoneRecoveryTokenPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RegisterSelfAssertedSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeOwnRecoveryTokenCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeRegistrantsRecoveryTokenCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\SelfVetSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\IdentityCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\RegistrationMailService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTest;

/**
 * @runTestsInSeparateProcesses
 */
class IdentityCommandHandlerSelfAssertedTokensTest extends CommandHandlerTest
{
    private static int $window = 3600;

    private AllowedSecondFactorListService&MockInterface $allowedSecondFactorListServiceMock;

    private IdentityProjectionRepository&MockInterface $identityProjectionRepository;

    private SecondFactorTypeService&MockInterface $secondFactorTypeService;

    private SecondFactorProvePossessionHelper&MockInterface $secondFactorProvePossessionHelper;

    private InstitutionConfigurationOptionsService&MockInterface $configService;

    private LoaResolutionService&MockInterface $loaResolutionService;

    /**
     * @var IdentityId
     */
    private IdentityId $id;

    /**
     * @var Institution
     */
    private Institution $institution;

    /**
     * @var Email
     */
    private Email $email;

    /**
     * @var CommonName
     */
    private CommonName $commonName;

    /**
     * @var Locale
     */
    private Locale $preferredLocale;
    /**
     * @var RecoveryTokenSecretHelper|MockInterface
     */
    private RecoveryTokenSecretHelper|MockInterface $recoveryTokenSecretHelper;

    private ?NameId $nameId = null;

    public function setUp(): void
    {
        $this->allowedSecondFactorListServiceMock = m::mock(AllowedSecondFactorListService::class);
        $this->loaResolutionService = m::mock(LoaResolutionService::class);

        $this->id = new IdentityId(self::uuid());
        $this->institution = new Institution('A Corp.');
        $this->email = new Email('info@domain.invalid');
        $this->commonName = new CommonName('Henk Westbroek');
        $this->preferredLocale = new Locale('en_GB');

        parent::setUp();
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_sms_recovery_code_possession_can_be_proven(): void
    {
        $recoveryTokenId = new RecoveryTokenId(self::uuid());
        $phoneNumber = new PhoneNumber('+31 (0) 612345678');

        $command = new ProvePhoneRecoveryTokenPossessionCommand();
        $command->identityId = (string)$this->id;
        $command->recoveryTokenId = (string)$recoveryTokenId;
        $command->phoneNumber = (string)$phoneNumber;

        $confMock = m::mock(InstitutionConfigurationOptions::class);
        $confMock->selfAssertedTokensOption = new SelfAssertedTokensOption(true);
        $this->configService->shouldReceive('findInstitutionConfigurationOptionsFor')->andReturn($confMock);

        $this->scenario
            ->withAggregateId($this->id)
            ->given([$this->buildIdentityCreatedEvent()])
            ->when($command)
            ->then([
                new PhoneRecoveryTokenPossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    $phoneNumber,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                ),
            ]);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_safe_store_secret_recovery_code_possession_can_be_proven(): void
    {
        $recoveryTokenId = new RecoveryTokenId(self::uuid());
        $secret = m::mock(HashedSecret::class);

        $command = new PromiseSafeStoreSecretTokenPossessionCommand();
        $command->identityId = (string)$this->id;
        $command->recoveryTokenId = (string)$recoveryTokenId;
        $command->secret = 'super-safe-secret';

        $confMock = m::mock(InstitutionConfigurationOptions::class);
        $confMock->selfAssertedTokensOption = new SelfAssertedTokensOption(true);
        $this->configService->shouldReceive('findInstitutionConfigurationOptionsFor')->andReturn($confMock);

        $this->recoveryTokenSecretHelper
            ->shouldReceive('hash')
            ->with(
                m::on(function ($unhashedSecret): bool {
                    $isUnhashedSecret = $unhashedSecret instanceof UnhashedSecret;
                    $hasExpectedSecret = $unhashedSecret->getSecret() === 'super-safe-secret';
                    return $isUnhashedSecret && $hasExpectedSecret;
                }),
            )
            ->andReturn($secret);

        $this->scenario
            ->withAggregateId($this->id)
            ->given([$this->buildIdentityCreatedEvent()])
            ->when($command)
            ->then([
                new SafeStoreSecretRecoveryTokenPossessionPromisedEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    new SafeStore($secret),
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                ),
            ]);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_safe_store_secret_and_phone_recovery_code_possession_can_be_proven(): void
    {
        $recoveryTokenId = new RecoveryTokenId(self::uuid());

        $phoneNumber = new PhoneNumber('+31 (0) 612345678');

        $command = new PromiseSafeStoreSecretTokenPossessionCommand();
        $command->identityId = (string)$this->id;
        $command->recoveryTokenId = (string)$recoveryTokenId;
        $command->secret = 'secret-for-safe-keeping';

        $command2 = new ProvePhoneRecoveryTokenPossessionCommand();
        $command2->identityId = (string)$this->id;
        $command2->recoveryTokenId = (string)$recoveryTokenId;
        $command2->phoneNumber = (string)$phoneNumber;

        $secret = new HashedSecret('secret-for-safe-keeping');
        $this->recoveryTokenSecretHelper
            ->shouldReceive('hash')
            ->with(
                m::on(function ($unhashedSecret): bool {
                    $isUnhashedSecret = $unhashedSecret instanceof UnhashedSecret;
                    $hasExpectedSecret = $unhashedSecret->getSecret() === 'secret-for-safe-keeping';
                    return $isUnhashedSecret && $hasExpectedSecret;
                }),
            )
            ->andReturn($secret);

        $confMock = m::mock(InstitutionConfigurationOptions::class);
        $confMock->selfAssertedTokensOption = new SelfAssertedTokensOption(true);
        $this->configService->shouldReceive('findInstitutionConfigurationOptionsFor')->andReturn($confMock);
        // Having both types of recovery tokens is allowed
        $this->scenario
            ->withAggregateId($this->id)
            ->given([$this->buildIdentityCreatedEvent()])
            ->when($command)
            ->then([
                new SafeStoreSecretRecoveryTokenPossessionPromisedEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    new SafeStore($secret),
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                ),
            ])
            ->when($command2)
            ->then([
                new PhoneRecoveryTokenPossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    $phoneNumber,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                ),
            ]);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_sms_recovery_code_possession_can_not_be_proven_twice(): void
    {
        $recoveryTokenId = new RecoveryTokenId(self::uuid());
        $phoneNumber = new PhoneNumber('+31 (0) 612345678');

        $command = new ProvePhoneRecoveryTokenPossessionCommand();
        $command->identityId = (string)$this->id;
        $command->recoveryTokenId = (string)$recoveryTokenId;
        $command->phoneNumber = (string)$phoneNumber;

        $confMock = m::mock(InstitutionConfigurationOptions::class);
        $confMock->selfAssertedTokensOption = new SelfAssertedTokensOption(true);
        $this->configService->shouldReceive('findInstitutionConfigurationOptionsFor')->andReturn($confMock);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Recovery token type sms is already registered');

        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->buildIdentityCreatedEvent(),
                new PhoneRecoveryTokenPossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    $phoneNumber,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                ),
            ])
            ->when($command);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_only_one_safe_store_secret_allowed(): void
    {
        $recoveryTokenId = new RecoveryTokenId(self::uuid());

        $command = new PromiseSafeStoreSecretTokenPossessionCommand();
        $command->identityId = (string)$this->id;
        $command->recoveryTokenId = (string)$recoveryTokenId;
        $command->secret = 'secret-for-safe-keeping';

        $confMock = m::mock(InstitutionConfigurationOptions::class);
        $confMock->selfAssertedTokensOption = new SelfAssertedTokensOption(true);
        $this->configService->shouldReceive('findInstitutionConfigurationOptionsFor')->andReturn($confMock);

        $secret = new HashedSecret('secret-for-safe-keeping');
        $this->recoveryTokenSecretHelper
            ->shouldReceive('hash')
            ->with(
                m::on(function ($unhashedSecret): bool {
                    $isUnhashedSecret = $unhashedSecret instanceof UnhashedSecret;
                    $hasExpectedSecret = $unhashedSecret->getSecret() === 'secret-for-safe-keeping';
                    return $isUnhashedSecret && $hasExpectedSecret;
                }),
            )
            ->andReturn($secret);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Recovery token type safe-store is already registered');

        $this->scenario
            ->withAggregateId($this->id)
            ->given([$this->buildIdentityCreatedEvent()])
            ->when($command)
            ->then([
                new SafeStoreSecretRecoveryTokenPossessionPromisedEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    new SafeStore($secret),
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                ),
            ])
            ->when($command);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_sms_recovery_token_possession_requires_institution_configuration_feature_enabled(): void
    {
        $identityCreatedEvent = $this->buildIdentityCreatedEvent();
        $recoveryTokenId = new RecoveryTokenId(self::uuid());
        $phoneNumber = new PhoneNumber('+31 (0) 612345678');
        $command = new ProvePhoneRecoveryTokenPossessionCommand();
        $command->identityId = (string)$this->id;
        $command->recoveryTokenId = (string)$recoveryTokenId;
        $command->phoneNumber = (string)$phoneNumber;

        $confMock = m::mock(InstitutionConfigurationOptions::class);
        $confMock->selfAssertedTokensOption = new SelfAssertedTokensOption(false);
        $this->configService->shouldReceive('findInstitutionConfigurationOptionsFor')->andReturn($confMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Registration of self-asserted tokens is not allowed for this institution "a corp.".',
        );

        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $identityCreatedEvent,
                new PhoneRecoveryTokenPossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    $phoneNumber,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                ),
            ])
            ->when($command);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_safe_store_secret_recovery_code_possession_can_be_revoked_by_ra(): void
    {
        $recoveryTokenId = new RecoveryTokenId(self::uuid());
        $secret = m::mock(HashedSecret::class);

        $confMock = m::mock(InstitutionConfigurationOptions::class);
        $confMock->selfAssertedTokensOption = new SelfAssertedTokensOption(true);
        $this->configService->shouldReceive('findInstitutionConfigurationOptionsFor')->andReturn($confMock);

        $authorityId = new IdentityId('authority_id_uuid');
        $authorityNameId = new NameId(self::uuid());
        $authorityInstitution = new Institution('Unseen University');
        $authorityEmail = new Email('lecturer@unseen.uni');
        $authorityCommonName = new CommonName('Lecturer in Recent Runes');

        $command = new RevokeRegistrantsRecoveryTokenCommand();
        $command->identityId = (string)$this->id;
        $command->recoveryTokenId = (string)$recoveryTokenId;
        $command->authorityId = (string)$authorityId;

        $this->scenario
            ->withAggregateId($authorityId)
            ->given([
                new IdentityCreatedEvent(
                    $authorityId,
                    $authorityInstitution,
                    $authorityNameId,
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $authorityId,
                    $authorityNameId,
                    $authorityInstitution,
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                    new SecondFactorId(self::uuid()),
                    new YubikeyPublicId('00000012'),
                ),
            ])
            ->withAggregateId($this->id)
            ->given([
                $this->buildIdentityCreatedEvent(),
                new SafeStoreSecretRecoveryTokenPossessionPromisedEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    new SafeStore($secret),
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                ),
            ])
            ->when($command)
            ->then([
                new CompliedWithRecoveryCodeRevocationEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    RecoveryTokenType::safeStore(),
                    $authorityId,
                ),
            ]);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_token_can_be_registered_self_asserted(): void
    {
        $recoveryTokenId = new RecoveryTokenId(self::uuid());
        $secret = m::mock(HashedSecret::class);

        $confMock = m::mock(InstitutionConfigurationOptions::class);
        $confMock->selfAssertedTokensOption = new SelfAssertedTokensOption(true);
        $this->configService->shouldReceive('findInstitutionConfigurationOptionsFor')->andReturn($confMock);

        $command = new RegisterSelfAssertedSecondFactorCommand();
        $command->authoringRecoveryTokenId = (string)$recoveryTokenId;
        $command->secondFactorType = 'yubikey';
        $command->secondFactorId = 'SFID';
        $command->secondFactorIdentifier = '00028278';
        $command->identityId = (string)$this->id;

        $secondFactorId = new SecondFactorId($command->secondFactorId);
        $yubikeyPublicId = new YubikeyPublicId($command->secondFactorIdentifier);

        $expectedVettingType = new SelfAssertedRegistrationVettingType($recoveryTokenId);

        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->buildIdentityCreatedEvent(),
                new YubikeyPossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    $yubikeyPublicId,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    new SecondFactorType('yubikey'),
                    $yubikeyPublicId,
                    DateTime::now(),
                    'REGCODE',
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                ),
                new SafeStoreSecretRecoveryTokenPossessionPromisedEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    new SafeStore($secret),
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                ),
            ])
            ->when($command)
            ->then([
                new SecondFactorVettedWithoutTokenProofOfPossession(
                    $this->id,
                    $this->nameId,
                    $this->institution,
                    $secondFactorId,
                    new SecondFactorType($command->secondFactorType),
                    $yubikeyPublicId,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                    $expectedVettingType,
                ),
            ]);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_self_asserted_token_registration_requires_possession_of_recovery_token(): void
    {
        $madeUpRecoveryTokenId = new RecoveryTokenId(self::uuid());

        $confMock = m::mock(InstitutionConfigurationOptions::class);
        $confMock->selfAssertedTokensOption = new SelfAssertedTokensOption(true);
        $this->configService->shouldReceive('findInstitutionConfigurationOptionsFor')->andReturn($confMock);

        $command = new RegisterSelfAssertedSecondFactorCommand();
        $command->authoringRecoveryTokenId = (string)$madeUpRecoveryTokenId;
        $command->secondFactorType = 'yubikey';
        $command->secondFactorId = 'SFID';
        $command->secondFactorIdentifier = '00028278';
        $command->identityId = (string)$this->id;

        $secondFactorId = new SecondFactorId($command->secondFactorId);
        $yubikeyPublicId = new YubikeyPublicId($command->secondFactorIdentifier);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A recovery token is required to perform a self-asserted token registration');
        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->buildIdentityCreatedEvent(),
                new YubikeyPossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    $yubikeyPublicId,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    new SecondFactorType('yubikey'),
                    $yubikeyPublicId,
                    DateTime::now(),
                    'REGCODE',
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                ),
            ])
            ->when($command);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_safe_store_secret_recovery_code_possession_can_be_revoked(): void
    {
        $recoveryTokenId = new RecoveryTokenId(self::uuid());
        $secret = m::mock(HashedSecret::class);

        $command = new RevokeOwnRecoveryTokenCommand();
        $command->identityId = (string)$this->id;
        $command->recoveryTokenId = (string)$recoveryTokenId;

        $confMock = m::mock(InstitutionConfigurationOptions::class);
        $confMock->selfAssertedTokensOption = new SelfAssertedTokensOption(true);
        $this->configService->shouldReceive('findInstitutionConfigurationOptionsFor')->andReturn($confMock);

        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->buildIdentityCreatedEvent(),
                new SafeStoreSecretRecoveryTokenPossessionPromisedEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    new SafeStore($secret),
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                ),
            ])
            ->when($command)
            ->then([
                new RecoveryTokenRevokedEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    RecoveryTokenType::safeStore(),
                ),
            ]);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_sat_token_can_be_used_to_self_vet_a_token(): void
    {
        $recoveryTokenId = new RecoveryTokenId(self::uuid());
        $secret = m::mock(HashedSecret::class);

        $confMock = m::mock(InstitutionConfigurationOptions::class);
        $confMock->selfAssertedTokensOption = new SelfAssertedTokensOption(true);
        $this->configService->shouldReceive('findInstitutionConfigurationOptionsFor')->andReturn($confMock);

        $secondFactorId = new SecondFactorId($this->uuid());
        $yubikeyPublicId = new YubikeyPublicId('12341234');

        $vettingType = new SelfAssertedRegistrationVettingType($recoveryTokenId);

        $loa = new Loa(1.5, 'loa-self-asserted');
        $this->loaResolutionService->shouldReceive('getLoa')->with('loa-self-asserted')->andReturn($loa);
        $phoneSfId = new SecondFactorId($this->uuid());
        $phoneIdentifier = new PhoneNumber('+31 (0) 612345678');

        $command = new SelfVetSecondFactorCommand();
        $command->secondFactorId = '+31 (0) 612345678';
        $command->registrationCode = 'REGCODE';
        $command->identityId = $this->id->getIdentityId();
        $command->authoringSecondFactorLoa = "loa-self-asserted";
        $command->secondFactorType = 'sms';

        $this->secondFactorTypeService->shouldReceive('getLevel')->andReturn(1.5);

        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->buildIdentityCreatedEvent(),
                new YubikeyPossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    $yubikeyPublicId,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    new SecondFactorType('yubikey'),
                    $yubikeyPublicId,
                    DateTime::now(),
                    'REGCODE',
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                ),
                new SafeStoreSecretRecoveryTokenPossessionPromisedEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    new SafeStore($secret),
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                ),
                new SecondFactorVettedWithoutTokenProofOfPossession(
                    $this->id,
                    $this->nameId,
                    $this->institution,
                    $secondFactorId,
                    new SecondFactorType($command->secondFactorType),
                    $yubikeyPublicId,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                    $vettingType,
                ),
                // The next token is self-vetted using the other SAT token
                new PhonePossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $phoneSfId,
                    $phoneIdentifier,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $this->id,
                    $this->institution,
                    $phoneSfId,
                    new SecondFactorType('sms'),
                    $phoneIdentifier,
                    DateTime::now(),
                    'REGCODE',
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                ),
            ])
            ->when($command)
            ->then([
                // When self vetting, proof of possession is skipped, no RA verification is performed.
                new SecondFactorVettedWithoutTokenProofOfPossession(
                    $this->id,
                    $this->nameId,
                    $this->institution,
                    $phoneSfId,
                    new SecondFactorType('sms'),
                    $phoneIdentifier,
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                    new SelfAssertedRegistrationVettingType($recoveryTokenId),
                ),
            ]);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_sat_not_allowed_when_one_vetted_token_is_identity_vetted(): void
    {
        $recoveryTokenId = new RecoveryTokenId(self::uuid());
        $secret = m::mock(HashedSecret::class);

        $confMock = m::mock(InstitutionConfigurationOptions::class);
        $confMock->selfAssertedTokensOption = new SelfAssertedTokensOption(true);
        $this->configService->shouldReceive('findInstitutionConfigurationOptionsFor')->andReturn($confMock);

        $secondFactorId = new SecondFactorId($this->uuid());
        $yubikeyPublicId = new YubikeyPublicId('12341234');

        $vettingType = new SelfAssertedRegistrationVettingType($recoveryTokenId);

        $loa = new Loa(1.5, 'loa-self-asserted');
        $this->loaResolutionService->shouldReceive('getLoa')->with('loa-self-asserted')->andReturn($loa);
        $phoneSfId = new SecondFactorId($this->uuid());
        $phoneIdentifier = new PhoneNumber('+31 (0) 612345678');

        $gsspId = new SecondFactorId('3c085c9a-a69e-4ebe-a17a-8f5aa1a579fb');
        $gsspIdentifier = new GssfId('identifier-for-a-gssp');

        $command = new SelfVetSecondFactorCommand();
        $command->secondFactorId = 'identifier-for-a-gssp';
        $command->registrationCode = 'REGCODE';
        $command->identityId = $this->id->getIdentityId();
        $command->authoringSecondFactorLoa = "loa-self-asserted";
        $command->secondFactorType = 'tiqr';

        $this->secondFactorTypeService->shouldReceive('getLevel')->andReturn(1.5);

        $this->allowedSecondFactorListServiceMock
            ->shouldReceive('getAllowedSecondFactorListFor')
            ->andReturn(
                AllowedSecondFactorList::ofTypes(
                    [
                        new SecondFactorType('tiqr'),
                        new SecondFactorType('yubikey'),
                        new SecondFactorType('sms'),
                    ],
                ),
            );

        $this->configService->shouldReceive('getMaxNumberOfTokensFor')->andReturn(5);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            'Not all tokens are self-asserted, it is not allowed to self-vet using the self-asserted token',
        );

        $this->scenario
            ->withAggregateId($this->id)
            ->given([
                $this->buildIdentityCreatedEvent(),
                new YubikeyPossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    $yubikeyPublicId,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $this->id,
                    $this->institution,
                    $secondFactorId,
                    new SecondFactorType('yubikey'),
                    $yubikeyPublicId,
                    DateTime::now(),
                    'REGCODE',
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                ),
                new SafeStoreSecretRecoveryTokenPossessionPromisedEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    new SafeStore($secret),
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                ),
                new SecondFactorVettedWithoutTokenProofOfPossession(
                    $this->id,
                    $this->nameId,
                    $this->institution,
                    $secondFactorId,
                    new SecondFactorType($command->secondFactorType),
                    $yubikeyPublicId,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                    $vettingType,
                ),
                // The next token is ra-vetted
                new PhonePossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $phoneSfId,
                    $phoneIdentifier,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $this->id,
                    $this->institution,
                    $phoneSfId,
                    new SecondFactorType('sms'),
                    $phoneIdentifier,
                    DateTime::now(),
                    'REGCODE',
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                ),
                new SecondFactorVettedEvent(
                    $this->id,
                    $this->nameId,
                    $this->institution,
                    $phoneSfId,
                    new SecondFactorType('sms'),
                    $phoneIdentifier,
                    $this->commonName,
                    $this->email,
                    $this->preferredLocale,
                    new OnPremiseVettingType(new DocumentNumber('123123')),
                ),
                // The third token is an attempt to self-vet a token
                new GssfPossessionProvenEvent(
                    $this->id,
                    $this->institution,
                    $gsspId,
                    new StepupProvider('tiqr'),
                    $gsspIdentifier,
                    true,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(self::$window),
                        DateTime::now(),
                    ),
                    'nonce',
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                ),
                new EmailVerifiedEvent(
                    $this->id,
                    $this->institution,
                    $gsspId,
                    new SecondFactorType('tiqr'),
                    $gsspIdentifier,
                    DateTime::now(),
                    'REGCODE',
                    $this->commonName,
                    $this->email,
                    new Locale('en_GB'),
                ),
            ])
            ->when($command);
    }

    protected function createCommandHandler(
        EventStoreInterface $eventStore,
        EventBusInterface $eventBus,
    ): CommandHandler {
        $aggregateFactory = new PublicConstructorAggregateFactory();

        $this->identityProjectionRepository = m::mock(IdentityProjectionRepository::class);
        $this->secondFactorTypeService = m::mock(SecondFactorTypeService::class);
        $this->secondFactorTypeService->shouldReceive('hasEqualOrHigherLoaComparedTo')->andReturn(true);
        $this->secondFactorProvePossessionHelper = m::mock(SecondFactorProvePossessionHelper::class);
        $this->configService = m::mock(InstitutionConfigurationOptionsService::class);
        $this->configService->shouldIgnoreMissing();
        $this->recoveryTokenSecretHelper = m::mock(RecoveryTokenSecretHelper::class);
        $registrationMailService = m::mock(RegistrationMailService::class);
        $logger = m::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();
        return new IdentityCommandHandler(
            new IdentityRepository(
                new IdentityIdEnforcingEventStoreDecorator($eventStore),
                $eventBus,
                $aggregateFactory,
                m::mock(UserDataFilterInterface::class),
                $logger,
            ),
            $this->identityProjectionRepository,
            ConfigurableSettings::create(self::$window, ['nl_NL', 'en_GB']),
            $this->allowedSecondFactorListServiceMock,
            $this->secondFactorTypeService,
            $this->secondFactorProvePossessionHelper,
            $this->configService,
            $this->loaResolutionService,
            $this->recoveryTokenSecretHelper,
            $registrationMailService,
        );
    }

    private function buildIdentityCreatedEvent(): IdentityCreatedEvent
    {
        $this->nameId = new NameId(md5(__METHOD__));

        return new IdentityCreatedEvent(
            $this->id,
            $this->institution,
            $this->nameId,
            $this->commonName,
            $this->email,
            $this->preferredLocale,
        );
    }
}
