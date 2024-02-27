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

namespace Surfnet\Stepup\Tests\Helper;

use Generator;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Helper\UserDataFilter;
use Surfnet\Stepup\Identity\Event\AppointedAsRaaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;

class UserDataFilterTest extends TestCase
{
    /**
     * @dataProvider provideEvents
     */
    public function test_filtering_is_applied_with_expected_result(
        IdentityCreatedEvent|PhonePossessionProvenAndVerifiedEvent|AppointedAsRaaForInstitutionEvent|PhonePossessionProvenEvent $event,
        array $expectation,
    ): void {
        $helper = new UserDataFilter();
        $data = $helper->filter($event);
        $this->assertSame($expectation, array_keys($data));
    }

    public function provideEvents(): Generator
    {
        $event = new IdentityCreatedEvent(
            new IdentityId("id"),
            new Institution("instititution"),
            new NameId("nameId"),
            new CommonName("commonName"),
            new Email("test@institution.nl"),
            new Locale("nl_NL"),
        );
        $expectation = [
            'id',
            'institution',
            'name_id',
            'preferred_locale',
            'common_name',
            'email',
        ];
        yield [$event, $expectation];

        $event = new PhonePossessionProvenAndVerifiedEvent(
            new IdentityId("id"),
            new Institution("institution"),
            new SecondFactorId("512312312"),
            new PhoneNumber("+0 (0) 000000000"),
            new CommonName("common"),
            new Email("test@example.com"),
            new Locale("nl_NL"),
            new DateTime(),
            "Y3MWWNDR",
        );
        $expectation = [
            'identity_id',
            'identity_institution',
            'second_factor_id',
            'registration_requested_at',
            'preferred_locale',
            'common_name',
            'email',
            'second_factor_type',
            'second_factor_identifier',
        ];
        yield [$event, $expectation];

        $event = new AppointedAsRaaForInstitutionEvent(
            new IdentityId("id"),
            new Institution("institution"),
            new NameId("nameId"),
            new Institution("ra"),
        );
        $expectation = [
            'identity_id',
            'institution',
            'name_id',
            'ra_institution',
        ];
        yield [$event, $expectation];

        $event = new PhonePossessionProvenEvent(
            new IdentityId("id"),
            new Institution("institution"),
            new SecondFactorId("52"),
            new PhoneNumber("+0 (0) 000000000"),
            true,
            emailVerificationWindow::createWindowFromTill(new DateTime(), new DateTime()),
            "30c0fcb136bf324eea652d5b86c1a08c",
            new CommonName("commonname"),
            new Email("test@example.com"),
            new Locale("nl_NL"),
        );
        $expectation = [
            'identity_id',
            'identity_institution',
            'second_factor_id',
            'preferred_locale',
            'common_name',
            'email',
            'second_factor_type',
            'second_factor_identifier',
        ];
        yield [$event, $expectation];
    }
}
