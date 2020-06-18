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
use Surfnet\Stepup\Identity\Value\StepupProvider;

class StepupProviderTest extends UnitTest
{
    /**
     * @test
     * @group        domain
     * @dataProvider invalidValueProvider
     *
     * @param mixed $invalidValue
     */
    public function a_stepup_provider_cannot_be_created_with_anything_but_a_nonempty_string($invalidValue)
    {
        $this->expectException(\Surfnet\Stepup\Exception\InvalidArgumentException::class);

        new StepupProvider($invalidValue);
    }

    /**
     * @test
     * @group domain
     */
    public function two_stepup_providers_with_the_same_value_are_equal()
    {
        $institution = new StepupProvider('a');
        $theSame     = new StepupProvider('a');
        $different   = new StepupProvider('A');

        $this->assertTrue($institution->equals($theSame));
        $this->assertFalse($institution->equals($different));
    }

    /**
     * DataProvider for {@see a_stepup_provider_cannot_be_created_with_anything_but_a_nonempty_string()}
     */
    public function invalidValueProvider()
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
