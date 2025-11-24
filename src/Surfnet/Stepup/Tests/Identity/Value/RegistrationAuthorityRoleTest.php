<?php

declare(strict_types=1);

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

namespace Surfnet\Stepup\Tests\Identity\Value;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;

class RegistrationAuthorityRoleTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    #[Test]
    #[Group('domain')]
    public function two_roles_with_the_same_value_are_equal(): void
    {
        $role = new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA);
        $theSame = new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_RAA);
        $different = new RegistrationAuthorityRole(RegistrationAuthorityRole::ROLE_SRAA);

        $this->assertTrue($role->equals($theSame));
        $this->assertFalse($role->equals($different));
    }
}
