<?php

/**
 * Copyright 2022 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Processor;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\Identity\Event\CompliedWithRecoveryCodeRevocationEvent;
use Surfnet\Stepup\Identity\Event\PhoneRecoveryTokenPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\RecoveryTokenRevokedEvent;
use Surfnet\Stepup\Identity\Event\SafeStoreSecretRecoveryTokenPossessionPromisedEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use Surfnet\Stepup\Identity\Value\SafeStore;
use Surfnet\Stepup\Identity\Value\UnhashedSecret;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\RecoveryTokenMailService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Processor\RecoveryTokenEmailProcessor;

class RecoveryTokenEmailProcessorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private RecoveryTokenEmailProcessor $processor;

    private RecoveryTokenMailService&MockInterface $mailService;

    private IdentityService&MockInterface $identityService;

    public function setUp(): void
    {
        $this->mailService = Mockery::mock(RecoveryTokenMailService::class);
        $this->identityService = Mockery::mock(IdentityService::class);
        $this->processor = new RecoveryTokenEmailProcessor(
            $this->mailService,
            $this->identityService,
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('processor')]
    public function test_mails_when_complied_with_recovery_token_revocation(): void
    {
        $identity = $this->returnABogusIdentity();
        $this->identityService
            ->shouldReceive('find')
            ->andReturn($identity);

        $event = new CompliedWithRecoveryCodeRevocationEvent(
            new IdentityId('my-id'),
            new Institution('Harderwijk University'),
            new RecoveryTokenId('r-t-id'),
            new RecoveryTokenType('safe-store'),
            new IdentityId('ra-id'),
        );

        $this->mailService
            ->shouldReceive('sendRevoked')
            ->once()
            ->with(
                $identity->preferredLocale,
                $identity->commonName,
                $identity->email,
                $event->recoveryTokenType,
                $event->recoveryTokenId,
                true,
            );
        $this->processor->handleCompliedWithRecoveryCodeRevocationEvent($event);
    }

    #[\PHPUnit\Framework\Attributes\Group('processor')]
    public function test_does_not_mail_when_identity_not_found_complied_with_recovery_token_revocation(): void
    {
        $this->identityService
            ->shouldReceive('find')
            ->andReturnNull();

        $event = new CompliedWithRecoveryCodeRevocationEvent(
            new IdentityId('my-not-found-id'),
            new Institution('Harderwijk University'),
            new RecoveryTokenId('r-t-id'),
            new RecoveryTokenType('safe-store'),
            new IdentityId('ra-id'),
        );

        $this->mailService
            ->shouldNotHaveReceived('sendRevoked');
        $this->processor->handleCompliedWithRecoveryCodeRevocationEvent($event);
    }

    #[\PHPUnit\Framework\Attributes\Group('processor')]
    public function test_it_mails_when_recovery_token_revoked_by_identity(): void
    {
        $identity = $this->returnABogusIdentity();
        $this->identityService
            ->shouldReceive('find')
            ->andReturn($identity);

        $event = new RecoveryTokenRevokedEvent(
            new IdentityId('my-id'),
            new Institution('Harderwijk University'),
            new RecoveryTokenId('r-t-id'),
            new RecoveryTokenType('safe-store'),
        );

        $this->mailService
            ->shouldReceive('sendRevoked')
            ->once()
            ->with(
                $identity->preferredLocale,
                $identity->commonName,
                $identity->email,
                $event->recoveryTokenType,
                $event->recoveryTokenId,
                false,
            );
        $this->processor->handleRecoveryTokenRevokedEvent($event);
    }

    #[\PHPUnit\Framework\Attributes\Group('processor')]
    public function test_does_not_mail_when_identity_not_found_recovery_token_revocation(): void
    {
        $this->identityService
            ->shouldReceive('find')
            ->andReturnNull();

        $event = new RecoveryTokenRevokedEvent(
            new IdentityId('my-not-found-id'),
            new Institution('Harderwijk University'),
            new RecoveryTokenId('r-t-id'),
            new RecoveryTokenType('safe-store'),
        );

        $this->mailService
            ->shouldNotHaveReceived('sendRevoked');
        $this->processor->handleRecoveryTokenRevokedEvent($event);
    }

    #[\PHPUnit\Framework\Attributes\Group('processor')]
    public function test_it_mails_when_sms_token_created(): void
    {
        $identity = $this->returnABogusIdentity();
        $this->identityService
            ->shouldReceive('find')
            ->andReturn($identity);

        $event = new PhoneRecoveryTokenPossessionProvenEvent(
            new IdentityId('my-id'),
            new Institution('Harderwijk University'),
            new RecoveryTokenId('r-t-id'),
            new PhoneNumber('+42 (0) 612345678'),
            $identity->commonName,
            $identity->email,
            $identity->preferredLocale,
        );

        $this->mailService
            ->shouldReceive('sendCreated')
            ->once()
            ->with(
                $identity->preferredLocale,
                $identity->commonName,
                $identity->email,
            );
        $this->processor->handlePhoneRecoveryTokenPossessionProvenEvent($event);
    }

    #[\PHPUnit\Framework\Attributes\Group('processor')]
    public function test_does_not_mail_when_identity_not_found_sms_creation(): void
    {
        $this->identityService
            ->shouldReceive('find')
            ->andReturnNull();

        $event = new PhoneRecoveryTokenPossessionProvenEvent(
            new IdentityId('my-id'),
            new Institution('Harderwijk University'),
            new RecoveryTokenId('r-t-id'),
            new PhoneNumber('+42 (0) 38473929281'),
            new CommonName('Jan de Wandelaar'),
            new Email('j.walker@example.com'),
            new Locale('nl_NL'),
        );

        $this->mailService
            ->shouldNotHaveReceived('sendCreated');
        $this->processor->handlePhoneRecoveryTokenPossessionProvenEvent($event);
    }

    #[\PHPUnit\Framework\Attributes\Group('processor')]
    public function test_it_mails_when_safe_store_token_created(): void
    {
        $identity = $this->returnABogusIdentity();
        $this->identityService
            ->shouldReceive('find')
            ->andReturn($identity);

        $event = new SafeStoreSecretRecoveryTokenPossessionPromisedEvent(
            new IdentityId('my-id'),
            new Institution('Harderwijk University'),
            new RecoveryTokenId('r-t-id'),
            new SafeStore(new UnhashedSecret('super-secret')),
            $identity->commonName,
            $identity->email,
            $identity->preferredLocale,
        );

        $this->mailService
            ->shouldReceive('sendCreated')
            ->once()
            ->with(
                $identity->preferredLocale,
                $identity->commonName,
                $identity->email,
            );
        $this->processor->handleSafeStoreSecretRecoveryTokenPossessionPromisedEvent($event);
    }

    #[\PHPUnit\Framework\Attributes\Group('processor')]
    public function test_does_not_mail_when_identity_not_found_safe_store_creation(): void
    {
        $this->identityService
            ->shouldReceive('find')
            ->andReturnNull();

        $event = new SafeStoreSecretRecoveryTokenPossessionPromisedEvent(
            new IdentityId('my-id'),
            new Institution('Harderwijk University'),
            new RecoveryTokenId('r-t-id'),
            new SafeStore(new UnhashedSecret('super-secret')),
            new CommonName('Jan de Wandelaar'),
            new Email('j.walker@example.com'),
            new Locale('nl_NL'),
        );

        $this->mailService
            ->shouldNotHaveReceived('sendCreated');
        $this->processor->handleSafeStoreSecretRecoveryTokenPossessionPromisedEvent($event);
    }

    private function returnABogusIdentity(): Identity
    {
        // Return a bogus Identity
        $identity = new Identity();
        $identity->preferredLocale = new Locale('nl_NL');
        $identity->commonName = new CommonName('Jan de Wandelaar');
        $identity->email = new Email('j.walker@example.com');
        return $identity;
    }
}
