<?php

/**
 * Copyright 2018 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not select this file except in compliance with the License.
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

namespace Surfnet\Stepup\Tests\Configuration\Value;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\SelectRaaOption;

class SelectRaaOptionTest extends TestCase
{
    /**
     * @test
     * @group domain
     */
    public function institution_entries_are_sorted()
    {
        $selectRaaOption = new SelectRaaOption(['z', 'y', 'x']);
        $this->assertEquals(['x', 'y', 'z'], $selectRaaOption->getInstitutions());
    }

    /**
     * @test
     * @group domain
     */
    public function select_raa_option_instances_can_be_compared()
    {
        $selectRaaOption = new SelectRaaOption(['z', 'y', 'x']);
        $secondSelectRaaOption = new SelectRaaOption(['y', 'x', 'z']);
        $this->assertTrue($selectRaaOption->equals($secondSelectRaaOption));
    }
}
