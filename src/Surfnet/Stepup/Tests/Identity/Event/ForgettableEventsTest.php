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

namespace Surfnet\Stepup\Tests\Identity\Event;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Surfnet\Stepup\Identity\Event\CompliedWithRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRestoredEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\PhoneRecoveryTokenPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\SafeStoreSecretRecoveryTokenPossessionPromisedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorMigratedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorMigratedToEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable;

final class ForgettableEventsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     * @group domain
     */
    public function certain_events_are_forgettable_events_and_others_are_not(): void
    {
        $forgettableEventFqcns = [
            CompliedWithRevocationEvent::class,
            CompliedWithUnverifiedSecondFactorRevocationEvent::class,
            CompliedWithVerifiedSecondFactorRevocationEvent::class,
            CompliedWithVettedSecondFactorRevocationEvent::class,
            EmailVerifiedEvent::class,
            GssfPossessionProvenEvent::class,
            GssfPossessionProvenAndVerifiedEvent::class,
            IdentityCreatedEvent::class,
            IdentityEmailChangedEvent::class,
            IdentityRenamedEvent::class,
            SafeStoreSecretRecoveryTokenPossessionPromisedEvent::class,
            SecondFactorMigratedEvent::class,
            SecondFactorMigratedToEvent::class,
            PhonePossessionProvenEvent::class,
            PhonePossessionProvenAndVerifiedEvent::class,
            PhoneRecoveryTokenPossessionProvenEvent::class,
            RegistrationAuthorityRetractedEvent::class,
            SecondFactorRevokedEvent::class,
            SecondFactorVettedEvent::class,
            SecondFactorVettedWithoutTokenProofOfPossession::class,
            U2fDevicePossessionProvenEvent::class,
            U2fDevicePossessionProvenAndVerifiedEvent::class,
            UnverifiedSecondFactorRevokedEvent::class,
            VerifiedSecondFactorRevokedEvent::class,
            VettedSecondFactorRevokedEvent::class,
            YubikeyPossessionProvenEvent::class,
            YubikeyPossessionProvenAndVerifiedEvent::class,
            YubikeySecondFactorBootstrappedEvent::class,
            RegistrationAuthorityRetractedForInstitutionEvent::class,
            IdentityRestoredEvent::class,
        ];
        $otherIdentityEventFqcns = array_diff($this->getConcreteIdentityEventFqcns(), $forgettableEventFqcns);
        $forgettableFqcn = Forgettable::class;

        foreach ($forgettableEventFqcns as $fqcn) {
            $this->assertTrue(
                is_a($fqcn, $forgettableFqcn, true),
                sprintf('%s is not a Forgettable event, please implement %s', $fqcn, $forgettableFqcn),
            );
        }

        foreach ($otherIdentityEventFqcns as $fqcn) {
            $this->assertFalse(
                is_a($fqcn, $forgettableFqcn, true),
                sprintf('%s is a Forgettable event, is this correct? Then add it to the list', $fqcn),
            );
        }
    }

    /**
     * @return string[]
     * @throws ReflectionException
     * @throws ReflectionException
     */
    private function getConcreteIdentityEventFqcns(): array
    {
        $files = glob(__DIR__ . '/../../../Identity/Event/*Event.php');

        if($files === false){
            return [];
        }

        return array_filter(
            array_map(
                static function ($file): ?string {
                    $fqcn = sprintf(
                        'Surfnet\Stepup\Identity\Event\%s',
                        preg_replace('/\\..+?$/', '', basename($file)),
                    );
                    $reflection = new ReflectionClass($fqcn);
                    return $reflection->isInstantiable() ? $fqcn : null;
                },
                $files,
            ),
        );
    }
}
