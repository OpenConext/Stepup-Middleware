<?php

/**
 * Copyright 2018 SURFnet bv
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
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Exception\InvalidArgumentException;

class InstitutionRoleTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidConstructorArgumentsProvider')]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function invalid_types_are_rejected_during_construction(bool|string|int $arguments): void
    {
        $this->expectException(InvalidArgumentException::class);
        new InstitutionRole($arguments);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('institutionTypeProvider')]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function institution_roles_can_be_created_by_type(string $type): void
    {
        $role1 = new InstitutionRole($type);
        $role2 = new InstitutionRole($type);

        $this->assertEquals($type, $role1->getType());
        $this->assertTrue($role1->equals($role2));
    }

    public static function institutionTypeProvider(): array
    {
        return [
            'use_ra' => ['use_ra'],
            'use_raa' => ['use_raa'],
            'select_raa' => ['select_raa'],
        ];
    }

    public static function invalidConstructorArgumentsProvider(): array
    {
        return [
            'cant-be-boolean' => [false],
            'cant-be-object' => ['invalid'],
            'cant-be-integer' => [42],
        ];
    }
}
