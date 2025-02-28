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

namespace Surfnet\Stepup\Tests\Identity\Value;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as UnitTest;
use StdClass;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Value\PhoneNumber;

class PhoneNumberTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     * @group        domain
     * @dataProvider invalidValueProvider
     */
    public function a_phone_number_cannot_be_created_with_anything_but_a_nonempty_string(
        string $invalidValue,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new PhoneNumber($invalidValue);
    }

    /**
     * @test
     * @group domain
     */
    public function two_phone_numbers_with_the_same_value_are_equal(): void
    {
        $one = new PhoneNumber('+31 (0) 12345678');
        $theSame = new PhoneNumber('+31 (0) 12345678');
        $different = new PhoneNumber('+31 (0) 87654321');
        $unknown = PhoneNumber::unknown();

        $this->assertTrue($one->equals($theSame));
        $this->assertFalse($one->equals($different));
        $this->assertFalse($one->equals($unknown));
    }

    /**
     * DataProvider for {@see a_phonenumber_cannot_be_created_with_anything_but_a_nonempty_string()}
     */
    public function invalidValueProvider(): array
    {
        return [
            'empty string' => [''],
            'blank string' => ['   '],
        ];
    }
}
