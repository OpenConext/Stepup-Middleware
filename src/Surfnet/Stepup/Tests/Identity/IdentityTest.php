<?php

/**
 * Copyright 2026 SURFnet bv
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

namespace Surfnet\Stepup\Tests\Identity;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as UnitTest;
use ReflectionMethod;
use Surfnet\Stepup\Identity\Entity\RecoveryToken;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\PhoneRecoveryTokenPossessionProvenEvent;
use Surfnet\Stepup\Identity\Identity;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\RecoveryTokenId;

class IdentityTest extends UnitTest
{
    #[Test]
    #[Group('domain')]
    public function get_child_entities_includes_registered_recovery_tokens(): void
    {
        $identityId = new IdentityId('A');
        $institution = new Institution('Helsingin Yliopisto');
        $recoveryTokenId = new RecoveryTokenId('RT-ID');

        $identity = new Identity();
        $identity->initializeState(new DomainEventStream([
            $this->wrap($identityId, new IdentityCreatedEvent(
                $identityId,
                $institution,
                new NameId('urn:eeva-kuopio'),
                new CommonName('Eeva Kuopio'),
                new Email('e.kuopio@hy.fi'),
                new Locale('fi_FI'),
            ), 0),
            $this->wrap($identityId, new PhoneRecoveryTokenPossessionProvenEvent(
                $identityId,
                $institution,
                $recoveryTokenId,
                new PhoneNumber('+31 (0) 12345678'),
                new CommonName('Eeva Kuopio'),
                new Email('e.kuopio@hy.fi'),
                new Locale('fi_FI'),
            ), 1),
        ]));

        $getChildEntities = new ReflectionMethod(Identity::class, 'getChildEntities');
        $getChildEntities->setAccessible(true);
        /** @var array<object> $childEntities */
        $childEntities = $getChildEntities->invoke($identity);

        $recoveryTokens = array_filter(
            $childEntities,
            static fn($entity): bool => $entity instanceof RecoveryToken,
        );
        $this->assertCount(
            1,
            $recoveryTokens,
            'Identity::getChildEntities() should dispatch IdentityForgottenEvent to registered RecoveryTokens',
        );
        $recoveryToken = array_values($recoveryTokens)[0];
        $this->assertSame(
            (string)$recoveryTokenId,
            (string)$recoveryToken->getTokenId(),
        );
    }

    private function wrap(IdentityId $identityId, object $event, int $playhead): DomainMessage
    {
        return DomainMessage::recordNow(
            $identityId->getIdentityId(),
            $playhead,
            new Metadata([]),
            $event,
        );
    }
}
