<?php

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\Stepup\Tests\Configuration\Value;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;

class ShowRaaContactInformationOptionTest extends TestCase
{
    /**
     * @test
     * @group        domain
     * @group        institution-configuration-option
     * @dataProvider nonBooleanProvider
     *
     * @param mixed $nonBooleanProvider
     */
    public function show_raa_contact_information_option_can_only_be_boolean($nonBooleanProvider)
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\InvalidArgumentException');

        new ShowRaaContactInformationOption($nonBooleanProvider);
    }

    /**
     * @test
     * @group domain
     * @group institution-configuration-option
     */
    public function two_show_raa_contact_information_options_with_the_same_values_are_equal()
    {
        $option = true;

        $showRaaContactInformationOption = new ShowRaaContactInformationOption($option);
        $theSame              = new ShowRaaContactInformationOption($option);

        $this->assertTrue($showRaaContactInformationOption->equals($theSame));
    }

    /**
     * @test
     * @group domain
     * @group institution-configuration-option
     */
    public function two_show_raa_contact_information_options_with_different_values_are_not_equal()
    {
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $different            = new ShowRaaContactInformationOption(false);

        $this->assertFalse($showRaaContactInformationOption->equals($different));
    }

    public function nonBooleanProvider()
    {
        return [
            'string'       => [''],
            'array'        => [[]],
            'integer'      => [1],
            'float'        => [1.2],
            'object'       => [new \StdClass()],
        ];
    }
}
