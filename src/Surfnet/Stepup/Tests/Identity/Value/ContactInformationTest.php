<?php

declare(strict_types=1);

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

namespace Surfnet\Stepup\Tests\Identity\Value;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Identity\Value\ContactInformation;

class ContactInformationTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function two_instances_with_the_same_value_are_equal(): void
    {
        $contactInformation = new ContactInformation('a');
        $theSame = new ContactInformation('a');
        $theSameWithSpaces = new ContactInformation('  a ');
        $different = new ContactInformation('A');

        $this->assertTrue($contactInformation->equals($theSame));
        $this->assertTrue($contactInformation->equals($theSameWithSpaces));
        $this->assertFalse($contactInformation->equals($different));
    }
}
