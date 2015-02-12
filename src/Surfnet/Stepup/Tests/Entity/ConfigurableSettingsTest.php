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

namespace Surfnet\Stepup\Tests\Entity;

use DateTime as CoreDateTime;
use PHPUnit_Framework_TestCase as UnitTest;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Entity\ConfigurableSettings;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\DateTimeHelper;

class ConfigurableSettingsTest extends UnitTest
{
    /**
     * @test
     * @group domain
     */
    public function a_new_email_verification_window_always_starts_now()
    {
        $settings = ConfigurableSettings::create(3);

        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@1')));
        $window = $settings->createNewEmailVerificationWindow();

        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@0')));
        $this->assertFalse($window->isOpen());
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@1')));
        $this->assertTrue($window->isOpen());

        // create a new window after some time has passed
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@100')));
        $secondWindow = $settings->createNewEmailVerificationWindow();

        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@1')));
        $this->assertFalse($secondWindow->isOpen());
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@100')));
        $this->assertTrue($secondWindow->isOpen());
    }
}
