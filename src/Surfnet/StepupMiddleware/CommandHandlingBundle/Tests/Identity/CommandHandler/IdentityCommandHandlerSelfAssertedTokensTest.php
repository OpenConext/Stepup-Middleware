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
use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Configuration\Value\SelfAssertedTokensOption;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Helper\RecoveryTokenSecretHelper;
use Surfnet\Stepup\Helper\SecondFactorProvePossessionHelper;
use Surfnet\Stepup\Helper\UserDataFilterInterface;
use Surfnet\Stepup\Identity\Entity\ConfigurableSettings;
use Surfnet\Stepup\Identity\Event\CompliedWithRecoveryCodeRevocationEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\PhoneRecoveryTokenPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\RecoveryTokenRevokedEvent;
use Surfnet\Stepup\Identity\Event\SafeStoreSecretRecoveryTokenPossessionPromisedEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\HashedSecret;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use Surfnet\Stepup\Identity\Value\SafeStore;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\UnhashedSecret;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\InstitutionConfigurationOptions;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\AllowedSecondFactorListService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionConfigurationOptionsService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository as IdentityProjectionRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\PromiseSafeStoreSecretTokenPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhoneRecoveryTokenPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeOwnRecoveryTokenCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeRegistrantsRecoveryTokenCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\IdentityCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTest;


/**
 * @runTestsInSeparateProcesses
 */
class IdentityCommandHandlerSelfAssertedTokensTest extends CommandHandlerTest
{
    private static $window = 3600;

    /**
     * @var AllowedSecondFactorListService|m\MockInterface
     */
    private $allowedSecondFactorListServiceMock;

    /**
     * @var m\MockInterface|IdentityProjectionRepository
     */
    private $identityProjectionRepository;

    /**
     * @var SecondFactorTypeService
     */
    private $secondFactorTypeService;

    /**
     * @var SecondFactorProvePossessionHelper|m\MockInterface
     */
    private $secondFactorProvePossessionHelper;

    /**
     * @var InstitutionConfigurationOptionsService $configService
     */
    private $configService;

    /**
     * @var LoaResolutionService
     */
    private $loaResolutionService;

    /**
     * @var IdentityId
     */
    private $id;

    /**
     * @var Institution
     */
    private $institution;

    /**
     * @var Email
     */
    private $email;

    /**
     * @var CommonName
     */
    private $commonName;

    /**
     * @var Locale
     */
    private $preferredLocale;
    /**
     * @var RecoveryTokenSecretHelper|m\MockInterface
     */
    private $recoveryTokenSecretHelper;

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
    public function test_a_sms_recovery_code_possession_can_be_proven()
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
                    $this->preferredLocale
                )
            ]);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_safe_store_secret_recovery_code_possession_can_be_proven()
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
            ->with(m::on(function ($unhashedSecret) {
                $isUnhashedSecret = $unhashedSecret instanceof UnhashedSecret;
                $hasExpectedSecret = $unhashedSecret->getSecret() === 'super-safe-secret';
                return $isUnhashedSecret && $hasExpectedSecret;
            }))
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
                    $this->preferredLocale
                )
            ]);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_safe_store_secret_and_phone_recovery_code_possession_can_be_proven()
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
            ->with(m::on(function ($unhashedSecret) {
                $isUnhashedSecret = $unhashedSecret instanceof UnhashedSecret;
                $hasExpectedSecret = $unhashedSecret->getSecret() === 'secret-for-safe-keeping';
                return $isUnhashedSecret && $hasExpectedSecret;
            }))
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
                    $this->preferredLocale
                )
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
                    $this->preferredLocale
                )
            ]);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_sms_recovery_code_possession_can_not_be_proven_twice()
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
                    $this->preferredLocale
                )
            ])
            ->when($command);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_only_one_safe_store_secret_allowed()
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
            ->with(m::on(function ($unhashedSecret) {
                $isUnhashedSecret = $unhashedSecret instanceof UnhashedSecret;
                $hasExpectedSecret = $unhashedSecret->getSecret() === 'secret-for-safe-keeping';
                return $isUnhashedSecret && $hasExpectedSecret;
            }))
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
                    $this->preferredLocale
                )
            ])
            ->when($command);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_sms_recovery_token_possession_requires_institution_configuration_feature_enabled()
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
        $this->expectExceptionMessage('Registration of self-asserted tokens is not allowed for this institution "a corp.".');

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
                    $this->preferredLocale
                )
            ])
            ->when($command);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_safe_store_secret_recovery_code_possession_can_be_revoked_by_ra()
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
                    new Locale('en_GB')
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    $authorityId,
                    $authorityNameId,
                    $authorityInstitution,
                    $authorityCommonName,
                    $authorityEmail,
                    new Locale('en_GB'),
                    new SecondFactorId(self::uuid()),
                    new YubikeyPublicId('00000012')
                )
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
                    $this->preferredLocale
                )
            ])
            ->when($command)
            ->then([
                new CompliedWithRecoveryCodeRevocationEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    RecoveryTokenType::safeStore(),
                    $authorityId
                )
            ]);
    }

    /**
     * @group command-handler
     * @runInSeparateProcess
     */
    public function test_a_safe_store_secret_recovery_code_possession_can_be_revoked()
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
                    $this->preferredLocale
                )
            ])
            ->when($command)
            ->then([
                new RecoveryTokenRevokedEvent(
                    $this->id,
                    $this->institution,
                    $recoveryTokenId,
                    RecoveryTokenType::safeStore()
                )
            ]);
    }

    protected function createCommandHandler(EventStoreInterface $eventStore, EventBusInterface $eventBus): CommandHandler
    {
        $aggregateFactory = new PublicConstructorAggregateFactory();

        $this->identityProjectionRepository = m::mock(IdentityProjectionRepository::class);
        $this->secondFactorTypeService = m::mock(SecondFactorTypeService::class);
        $this->secondFactorTypeService->shouldReceive('hasEqualOrHigherLoaComparedTo')->andReturn(true);
        $this->secondFactorProvePossessionHelper = m::mock(SecondFactorProvePossessionHelper::class);
        $this->configService = m::mock(InstitutionConfigurationOptionsService::class);
        $this->configService->shouldIgnoreMissing();
        $this->recoveryTokenSecretHelper = m::mock(RecoveryTokenSecretHelper::class);
        $logger = m::mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();
        return new IdentityCommandHandler(
            new IdentityRepository(
                new IdentityIdEnforcingEventStoreDecorator($eventStore),
                $eventBus,
                $aggregateFactory,
                m::mock(UserDataFilterInterface::class),
                $logger
            ),
            $this->identityProjectionRepository,
            ConfigurableSettings::create(self::$window, ['nl_NL', 'en_GB']),
            $this->allowedSecondFactorListServiceMock,
            $this->secondFactorTypeService,
            $this->secondFactorProvePossessionHelper,
            $this->configService,
            $this->loaResolutionService,
            $this->recoveryTokenSecretHelper
        );
    }

    private function buildIdentityCreatedEvent()
    {
        $nameId = new NameId(md5(__METHOD__));

        return new IdentityCreatedEvent(
            $this->id,
            $this->institution,
            $nameId,
            $this->commonName,
            $this->email,
            $this->preferredLocale
        );
    }
}
