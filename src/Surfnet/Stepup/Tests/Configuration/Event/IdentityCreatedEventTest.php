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
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;


class IdentityCreatedEventTest extends TestCase
{
    public function test_if_all_the_expected_data_is_returned()
    {
        $IdentityCreatedEvent = new IdentityCreatedEvent(
            new IdentityId("id"),
            new Institution("HZ"),
            new NameId("nameId"),
            new CommonName("commonName"),
            new Email("test@hz.nl"),
            new Locale("nl_NL"));

        $userData = $IdentityCreatedEvent->obtainUserData();

        $this->assertArrayHasKey("id", $userData);
        $this->assertArrayHasKey("institution", $userData);
        $this->assertArrayHasKey("name_id", $userData);
        $this->assertArrayHasKey("preferred_locale", $userData);
        $this->assertArrayHasKey("common_name", $userData);
        $this->assertArrayHasKey("email", $userData);
    }
}
