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

namespace Surfnet\Stepup\Tests\Identity\Entity;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Identity\Entity\RecoveryToken;
use Surfnet\Stepup\Identity\Entity\RecoveryTokenCollection;
use Surfnet\Stepup\Identity\Identity;
use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;

class RecoveryTokenCollectionTest extends UnitTest
{
    #[Test]
    #[Group('domain')]
    public function get_values_returns_all_recovery_tokens_in_the_collection(): void
    {
        $collection = new RecoveryTokenCollection();
        $first = $this->createRecoveryToken('RT-1');
        $second = $this->createRecoveryToken('RT-2');

        $collection->set($first);
        $collection->set($second);

        $this->assertSame([$first, $second], $collection->getValues());
    }

    #[Test]
    #[Group('domain')]
    public function get_values_returns_an_empty_array_for_an_empty_collection(): void
    {
        $collection = new RecoveryTokenCollection();

        $this->assertSame([], $collection->getValues());
    }

    private function createRecoveryToken(string $id): RecoveryToken
    {
        return RecoveryToken::create(
            new RecoveryTokenId($id),
            RecoveryTokenType::sms(),
            new Identity(),
        );
    }
}
