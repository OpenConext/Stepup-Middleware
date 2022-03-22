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
use Surfnet\Stepup\Identity\Event\AppointedAsRaaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;


class AppointedAsRaaForInstitutionEventTest extends TestCase
{
    public function test_if_all_whitelisted_data_is_returned()
    {
        $appointedAsRaaForInstitutionEvent = new AppointedAsRaaForInstitutionEvent(
            new IdentityId("id"),
            new Institution("HZ"),
            new NameId("nameId"),
            new Institution("ra")
        );

        $filteredUserData = $appointedAsRaaForInstitutionEvent->obtainUserData();

        $this->assertArrayHasKey("identity_id", $filteredUserData);
        $this->assertArrayHasKey("institution", $filteredUserData);
        $this->assertArrayHasKey("name_id", $filteredUserData);
        $this->assertArrayHasKey("ra_institution", $filteredUserData);
    }
}
