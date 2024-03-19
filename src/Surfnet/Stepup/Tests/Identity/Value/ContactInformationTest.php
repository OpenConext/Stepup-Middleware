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
use Surfnet\Stepup\Identity\Value\ContactInformation;

class ContactInformationTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     * @group        domain
     * @dataProvider invalidValueProvider
     */
    public function it_cannot_be_created_with_anything_but_a_nonempty_string(int|float|StdClass|array $invalidValue,): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ContactInformation($invalidValue);
    }

    /**
     * @test
     * @group domain
     */
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

    /**
     * dataprovider
     */
    public function invalidValueProvider(): array
    {
        return [
            'array' => [[]],
            'integer' => [1],
            'float' => [1.2],
            'object' => [new StdClass()],
        ];
    }
}
