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

use PHPUnit\Framework\TestCase as TestCase;

final class ForgettableEventsTest extends TestCase
{
    /**
     * @test
     * @group domain
     */
    public function certain_events_are_forgettable_events_and_others_are_not()
    {
        $forgettableEventFqcns = [
            'Surfnet\Stepup\Identity\Event\CompliedWithRevocationEvent',
            'Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent',
            'Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent',
            'Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent',
            'Surfnet\Stepup\Identity\Event\EmailVerifiedEvent',
            'Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent',
            'Surfnet\Stepup\Identity\Event\GssfPossessionProvenAndVerifiedEvent',
            'Surfnet\Stepup\Identity\Event\IdentityCreatedEvent',
            'Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent',
            'Surfnet\Stepup\Identity\Event\IdentityRenamedEvent',
            'Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent',
            'Surfnet\Stepup\Identity\Event\PhonePossessionProvenAndVerifiedEvent',
            'Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedEvent',
            'Surfnet\Stepup\Identity\Event\SecondFactorRevokedEvent',
            'Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent',
            'Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenEvent',
            'Surfnet\Stepup\Identity\Event\U2fDevicePossessionProvenAndVerifiedEvent',
            'Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent',
            'Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent',
            'Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent',
            'Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent',
            'Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenAndVerifiedEvent',
            'Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent',
            'Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedForInstitutionEvent',
        ];
        $otherIdentityEventFqcns = array_diff($this->getConcreteIdentityEventFqcns(), $forgettableEventFqcns);
        $forgettableFqcn = 'Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\Forgettable';

        foreach ($forgettableEventFqcns as $fqcn) {
            $this->assertTrue(
                is_a($fqcn, $forgettableFqcn, true),
                sprintf('%s is not a Forgettable event, please implement %s', $fqcn, $forgettableFqcn)
            );
        }

        foreach ($otherIdentityEventFqcns as $fqcn) {
            $this->assertFalse(
                is_a($fqcn, $forgettableFqcn, true),
                sprintf('%s is a Forgettable event, is this correct? Then add it to the list', $fqcn, $forgettableFqcn)
            );
        }
    }

    /**
     * @return string[]
     */
    private function getConcreteIdentityEventFqcns()
    {
        return array_filter(
            array_map(
                function ($file) {
                    $fqcn       = sprintf(
                        'Surfnet\Stepup\Identity\Event\%s',
                        preg_replace('/\\..+?$/', '', basename($file))
                    );
                    $reflection = new \ReflectionClass($fqcn);
                    return $reflection->isInstantiable() ? $fqcn : null;
                },
                glob(__DIR__ . '/../../../Identity/Event/*Event.php')
            )
        );
    }
}
