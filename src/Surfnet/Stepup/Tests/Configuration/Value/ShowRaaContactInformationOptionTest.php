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
use Surfnet\Stepup\Configuration\Value\ShowRaaContactInformationOption;
use Surfnet\Stepup\Exception\InvalidArgumentException;

class ShowRaaContactInformationOptionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    #[\PHPUnit\Framework\Attributes\Group('institution-configuration-option')]
    public function two_show_raa_contact_information_options_with_the_same_values_are_equal(): void
    {
        $option = true;

        $showRaaContactInformationOption = new ShowRaaContactInformationOption($option);
        $theSame = new ShowRaaContactInformationOption($option);

        $this->assertTrue($showRaaContactInformationOption->equals($theSame));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    #[\PHPUnit\Framework\Attributes\Group('institution-configuration-option')]
    public function two_show_raa_contact_information_options_with_different_values_are_not_equal(): void
    {
        $showRaaContactInformationOption = new ShowRaaContactInformationOption(true);
        $different = new ShowRaaContactInformationOption(false);

        $this->assertFalse($showRaaContactInformationOption->equals($different));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    #[\PHPUnit\Framework\Attributes\Group('institution-configuration-option')]
    public function default_value_is_true(): void
    {
        $default = ShowRaaContactInformationOption::getDefault();
        $true = new ShowRaaContactInformationOption(true);

        $this->assertTrue($default->equals($true));
    }
}
