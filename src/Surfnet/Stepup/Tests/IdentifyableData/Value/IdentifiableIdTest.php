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

namespace Surfnet\Stepup\Tests\IdentifyingData\Value;

use PHPUnit_Framework_TestCase as UnitTest;
use Surfnet\Stepup\IdentifyingData\Value\IdentifyingDataId;

class IdentifiableDataIdTest extends UnitTest
{
    /**
     * @test
     * @group        domain
     * @dataProvider invalidArgumentProvider
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     *
     * @param mixed $invalidValue
     */
    public function the_id_must_be_a_non_empty_string($invalidValue)
    {
        new IdentifyingDataId($invalidValue);
    }

    /**
     * @test
     * @group domain
     */
    public function two_Identifiable_data_ids_with_the_same_value_are_equal()
    {
        $identifiableDataId = new IdentifyingDataId('89d428f7-a65a-4fe4-bae8-bc19c8b26c83');
        $theSame            = new IdentifyingDataId('89d428f7-a65a-4fe4-bae8-bc19c8b26c83');
        $different          = new IdentifyingDataId('518d3f11-6218-4cad-bf80-4394c4174c6f');

        $this->assertTrue($identifiableDataId->equals($theSame));
        $this->assertFalse($identifiableDataId->equals($different));
    }

    /**
     * provider for {@see the_id_must_be_a_non_empty_string()}
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
