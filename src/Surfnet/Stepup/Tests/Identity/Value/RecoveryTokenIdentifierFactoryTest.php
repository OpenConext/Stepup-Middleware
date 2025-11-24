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

namespace Surfnet\Stepup\Tests\Identity\Value;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\Identity\Value\HashedSecret;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\RecoveryTokenIdentifierFactory;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use Surfnet\Stepup\Identity\Value\SafeStore;

final class RecoveryTokenIdentifierFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function test_generates_identifiers_of_all_types(): void
    {
        $this->assertEquals(
            new PhoneNumber('+31 (0) 12345678'),
            RecoveryTokenIdentifierFactory::forType(RecoveryTokenType::sms(), '+31 (0) 12345678'),
        );
        $this->assertEquals(
            new SafeStore(new HashedSecret('super-secret')),
            RecoveryTokenIdentifierFactory::forType(RecoveryTokenType::safeStore(), 'super-secret'),
        );

        $this->assertEquals(
            PhoneNumber::unknown(),
            RecoveryTokenIdentifierFactory::unknownForType(RecoveryTokenType::sms()),
        );
        $this->assertEquals(
            SafeStore::unknown(),
            RecoveryTokenIdentifierFactory::unknownForType(RecoveryTokenType::safeStore()),
        );
    }
}
