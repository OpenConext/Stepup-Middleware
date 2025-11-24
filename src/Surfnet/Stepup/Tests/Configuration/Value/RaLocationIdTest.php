<?php

declare(strict_types=1);

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
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Exception\InvalidArgumentException;

class RaLocationIdTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('nonStringOrEmptyStringProvider')]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function an_ra_location_id_cannot_be_created_with_anything_but_a_nonempty_string(
        string $nonStringOrEmptyString,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new RaLocationId($nonStringOrEmptyString);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function an_ra_location_id_cannot_be_created_with_anything_but_a_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $nonUuid = 'this-is-not-a-uuid';

        new RaLocationId($nonUuid);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function two_ra_location_ids_with_the_same_values_are_equal(): void
    {
        $uuid = $this->uuid();

        $raLocationId = new RaLocationId($uuid);
        $theSame = new RaLocationId($uuid);

        $this->assertTrue($raLocationId->equals($theSame));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\Group('domain')]
    public function two_ra_location_ids_with_different_values_are_not_equal(): void
    {
        $raLocationId = new RaLocationId($this->uuid());
        $different = new RaLocationId($this->uuid());

        $this->assertFalse($raLocationId->equals($different));
    }

    public static function nonStringOrEmptyStringProvider(): array
    {
        return [
            'empty string' => [''],
            'blank string' => ['   '],
        ];
    }

    private function uuid(): string
    {
        return (string)Uuid::uuid4();
    }
}
