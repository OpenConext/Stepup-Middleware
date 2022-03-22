<?php

/**
 * Copyright 2022 SURFnet B.V.
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

namespace Surfnet\Stepup\Tests\Configuration\Event;

use PHPUnit\Framework\TestCase as TestCase;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;

class PhonePossessionProvenAndVerifiedEventTest extends TestCase
{
    public function test_if_all_the_expected_data_is_returned()
    {
        $PhonePossessionProvenAndVerifiedEvent = new PhonePossessionProvenAndVerifiedEvent(
            new IdentityId("id"),
            new Institution("HZ"),
            new SecondFactorId("52"),
            new PhoneNumber("+0 (0) 000000000"),
            new CommonName("common"),
            new Email("test@gmail.com"),
            new Locale("nl_NL"),
            new DateTime(),
            "Y3MWWNDR"
        );

        $userData = $PhonePossessionProvenAndVerifiedEvent->obtainUserData();

        $this->assertArrayHasKey("identity_id", $userData);
        $this->assertArrayHasKey("identity_institution", $userData);
        $this->assertArrayHasKey("second_factor_id", $userData);
        $this->assertArrayHasKey("registration_requested_at", $userData);
        $this->assertArrayHasKey("preferred_locale", $userData);
        $this->assertArrayHasKey("second_factor_type", $userData);
        $this->assertArrayHasKey("second_factor_identifier", $userData);
        $this->assertArrayHasKey("common_name", $userData);

        //test if non whitelisted data is filtered out
        $this->assertArrayNotHasKey("registration_code", $userData);
    }
}
