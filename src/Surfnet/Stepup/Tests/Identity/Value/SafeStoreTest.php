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

use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\RecoveryTokenIdentifier;
use Surfnet\Stepup\Identity\Value\SafeStore;

class SafeStoreTest extends UnitTest
{
    /**
     * @group domain
     */
    public function test_creation_of_safe_store()
    {
        $instance = new SafeStore(password_hash('super-secret', PASSWORD_BCRYPT));
        $this->assertInstanceOf(RecoveryTokenIdentifier::class, $instance);
        $this->assertTrue(password_verify('super-secret', $instance->getValue()));
    }

    /**
     * @group domain
     */
    public function test_equals()
    {
        $safeStore = new SafeStore('a');
        $safeStore2 = new SafeStore('a');
        // For now this is the case, as the safe store is a marker token type
        $this->assertTrue($safeStore->equals($safeStore2));

        $phone = new PhoneNumber('+30 (0) 612314353');
        $this->assertFalse($safeStore->equals($phone));

        $safeStore3 = new SafeStore('b');
        $this->assertFalse($safeStore->equals($safeStore3));
    }
}
