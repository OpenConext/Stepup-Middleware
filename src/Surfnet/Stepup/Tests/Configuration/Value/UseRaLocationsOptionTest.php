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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as TestCase;
use StdClass;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\Stepup\Exception\InvalidArgumentException;

class UseRaLocationsOptionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     * @group domain
     * @group institution-configuration-option
     */
    public function two_use_ra_location_options_with_the_same_values_are_equal(): void
    {
        $option = true;

        $useRaLocationsOption = new UseRaLocationsOption($option);
        $theSame = new UseRaLocationsOption($option);

        $this->assertTrue($useRaLocationsOption->equals($theSame));
    }

    /**
     * @test
     * @group domain
     * @group institution-configuration-option
     */
    public function two_use_ra_location_options_with_different_values_are_not_equal(): void
    {
        $useRaLocationsOption = new UseRaLocationsOption(true);
        $different = new UseRaLocationsOption(false);

        $this->assertFalse($useRaLocationsOption->equals($different));
    }

    /**
     * @test
     * @group domain
     * @group institution-configuration-option
     */
    public function default_value_is_false(): void
    {
        $default = UseRaLocationsOption::getDefault();
        $false = new UseRaLocationsOption(false);

        $this->assertTrue($default->equals($false));
   }
}
