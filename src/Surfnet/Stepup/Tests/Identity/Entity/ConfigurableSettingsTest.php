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

namespace Surfnet\Stepup\Tests\Identity\Entity;

use DateTime as CoreDateTime;
use DateTimeZone;
use PHPUnit_Framework_TestCase as UnitTest;
use Surfnet\Stepup\DateTime\UtcDateTime;
use Surfnet\Stepup\Identity\Entity\ConfigurableSettings;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\DateTimeHelper;

class ConfigurableSettingsTest extends UnitTest
{
    /**
     * @test
     * @group domain
     */
    public function a_new_email_verification_window_always_starts_now()
    {
        $settings = ConfigurableSettings::create(3, []);

        DateTimeHelper::setCurrentTime(new UtcDateTime(new CoreDateTime('@1', new DateTimeZone('UTC'))));
        $window = $settings->createNewEmailVerificationWindow();

        DateTimeHelper::setCurrentTime(new UtcDateTime(new CoreDateTime('@0', new DateTimeZone('UTC'))));
        $this->assertFalse($window->isOpen());
        DateTimeHelper::setCurrentTime(new UtcDateTime(new CoreDateTime('@1', new DateTimeZone('UTC'))));
        $this->assertTrue($window->isOpen());

        // create a new window after some time has passed
        DateTimeHelper::setCurrentTime(new UtcDateTime(new CoreDateTime('@100', new DateTimeZone('UTC'))));
        $secondWindow = $settings->createNewEmailVerificationWindow();

        DateTimeHelper::setCurrentTime(new UtcDateTime(new CoreDateTime('@1', new DateTimeZone('UTC'))));
        $this->assertFalse($secondWindow->isOpen());
        DateTimeHelper::setCurrentTime(new UtcDateTime(new CoreDateTime('@100', new DateTimeZone('UTC'))));
        $this->assertTrue($secondWindow->isOpen());
    }

    public function localeVerifications()
    {
        return [
            'No app locales, false'                                => [false, 'nl_NL', []],
            'English app locale, Dutch locale, false'              => [false, 'nl_NL', ['en_GB']],
            'English, German app locales, Dutch locale, false'     => [false, 'nl_NL', ['en_GB', 'de_DE']],
            'English, Dutch app locales, Dutch locale, true'       => [true,  'nl_NL', ['en_GB', 'nl_NL']],
        ];
    }

    /**
     * @test
     * @group domain
     * @dataProvider localeVerifications
     * @param boolean  $isValid
     * @param string   $localeString
     * @param string[] $validLocaleStrings
     */
    public function a_locale_can_be_verified_to_be_a_valid_locale($isValid, $localeString, array $validLocaleStrings)
    {
        $configuration = ConfigurableSettings::create(3, $validLocaleStrings);

        $this->assertEquals($isValid, $configuration->isSupportedLocale(new Locale($localeString)));
    }
}
