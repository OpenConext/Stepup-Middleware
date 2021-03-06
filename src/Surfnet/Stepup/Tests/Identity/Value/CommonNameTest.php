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

use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Identity\Value\CommonName;

class CommonNameTest extends UnitTest
{
    /**
     * @test
     * @group        domain
     * @dataProvider invalidArgumentProvider
     *
     * @param mixed $invalidValue
     */
    public function the_common_name_address_must_be_a_non_empty_string($invalidValue)
    {
        $this->expectException(\Surfnet\Stepup\Exception\InvalidArgumentException::class);

        new CommonName($invalidValue);
    }

    /**
     * @test
     * @group domain
     */
    public function two_common_names_with_the_same_value_are_equal()
    {
        $commonName = new CommonName('John Doe');
        $theSame    = new CommonName('John Doe');
        $different  = new CommonName('Jane Doe');
        $unknown    = CommonName::unknown();

        $this->assertTrue($commonName->equals($theSame));
        $this->assertFalse($commonName->equals($different));
        $this->assertFalse($commonName->equals($unknown));
    }

    /**
     * provider for {@see the_common_name_address_must_be_a_non_empty_string()}
     */
    public function invalidArgumentProvider()
    {
        return [
            'empty string' => [''],
            'blank string' => ['   '],
            'array'        => [[]],
            'integer'      => [1],
            'float'        => [1.2],
            'object'       => [new \StdClass()],
        ];
    }
}
