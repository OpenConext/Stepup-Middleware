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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Doctrine\Type;

use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as UnitTest;
use stdClass;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\SecondFactorStatusType;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\SecondFactorStatus;

class SecondFactorStatusTypeTest extends UnitTest
{
    use MockeryPHPUnitIntegration;


    private MariaDBPlatform $platform;

    /**
     * Register the type, since we're forced to use the factory method.
     */
    public static function setUpBeforeClass(): void
    {
        Type::addType(SecondFactorStatusType::NAME, SecondFactorStatusType::class);
    }

    public function setUp(): void
    {
        $this->platform = new MariaDBPlatform();
    }

    public static function invalidPhpValues(): array
    {
        return [
            'null' => [null],
            'string' => ['string'],
            'int' => [9],
            'float' => [9.1],
            'array' => [[]],
            'object of a different type' => [new stdClass],
            'resource' => [fopen('php://memory', 'w')],
        ];
    }

    #[Test]
    #[DataProvider('invalidPhpValues')]
    #[Group('doctrine')]
    public function an_invalid_php_value_is_not_accepted_in_to_sql_conversion(mixed $value): void
    {
        $this->expectException(ConversionException::class);

        $type = Type::getType(SecondFactorStatusType::NAME);
        $type->convertToDatabaseValue($value, $this->platform);
    }

    public static function validPhpValues(): array
    {
        return [
            'unverified' => [SecondFactorStatus::unverified(), 0],
            'verified' => [SecondFactorStatus::verified(), 10],
            'vetted' => [SecondFactorStatus::vetted(), 20],
            'revoked' => [SecondFactorStatus::revoked(), 30],
            'forgotten' => [SecondFactorStatus::forgotten(), 40],
        ];
    }

    #[Test]
    #[DataProvider('validPhpValues')]
    #[Group('doctrine')]
    public function a_valid_php_value_is_converted_to_a_sql_value(mixed $phpValue, int $databaseValue): void
    {
        $type = Type::getType(SecondFactorStatusType::NAME);
        $this->assertSame($databaseValue, $type->convertToDatabaseValue($phpValue, $this->platform));
    }

    public static function invalidDatabaseValues(): array
    {
        return [
            'null' => [null],
            'invalid string' => ['string'],
            'int' => [9],
            'float' => [9.1],
            'array' => [[]],
            'object of a different type' => [new stdClass],
            'resource' => [fopen('php://memory', 'w')],
        ];
    }

    #[Test]
    #[DataProvider('invalidDatabaseValues')]
    #[Group('doctrine')]
    public function an_invalid_database_value_causes_an_exception_upon_conversion(mixed $input): void
    {
        $this->expectException(ConversionException::class);

        $type = Type::getType(SecondFactorStatusType::NAME);
        $type->convertToPHPValue($input, $this->platform);
    }

    public static function validDatabaseValues(): array
    {
        return [
            'unverified' => ['0', SecondFactorStatus::unverified()],
            'verified' => ['10', SecondFactorStatus::verified()],
            'vetted' => ['20', SecondFactorStatus::vetted()],
            'revoked' => ['30', SecondFactorStatus::revoked()],
            'forgotten' => ['40', SecondFactorStatus::forgotten()],
        ];
    }

    #[Test]
    #[DataProvider('validDatabaseValues')]
    #[Group('doctrine')]
    public function a_valid_database_value_is_converted_to_a_sql_value(string $databaseValue, mixed $phpValue): void
    {
        $type = Type::getType(SecondFactorStatusType::NAME);
        $this->assertTrue($phpValue->equals($type->convertToPHPValue($databaseValue, $this->platform)));
    }
}
