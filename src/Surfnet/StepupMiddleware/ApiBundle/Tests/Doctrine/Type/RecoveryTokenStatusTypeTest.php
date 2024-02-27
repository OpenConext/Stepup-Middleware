<?php

/**
 * Copyright 2022 SURFnet bv
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
use PHPUnit\Framework\TestCase as UnitTest;
use stdClass;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\RecoveryTokenStatusType;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RecoveryTokenStatus;

class RecoveryTokenStatusTypeTest extends UnitTest
{
    /**
     * @var MySqlPlatform
     */
    private MariaDBPlatform $platform;

    /**
     * Register the type, since we're forced to use the factory method.
     */
    public static function setUpBeforeClass(): void
    {
        Type::addType(RecoveryTokenStatusType::NAME, RecoveryTokenStatusType::class);
    }

    public function setUp(): void
    {
        $this->platform = new MariaDBPlatform();
    }

    public function invalidPhpValues(): array
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

    /**
     * @test
     * @dataProvider invalidPhpValues
     * @group doctrine
     */
    public function an_invalid_php_value_is_not_accepted_in_to_sql_conversion(mixed $value): void
    {
        $this->expectException(ConversionException::class);

        $type = Type::getType(RecoveryTokenStatusType::NAME);
        $type->convertToDatabaseValue($value, $this->platform);
    }

    public function validPhpValues(): array
    {
        return [
            'active' => [RecoveryTokenStatus::active(), 0],
            'revoked' => [RecoveryTokenStatus::revoked(), 10],
            'forgotten' => [RecoveryTokenStatus::forgotten(), 20],
        ];
    }

    /**
     * @test
     * @dataProvider validPhpValues
     * @group doctrine
     *
     * @param mixed $phpValue
     * @param mixed $databaseValue
     */
    public function a_valid_php_value_is_converted_to_a_sql_value(
        RecoveryTokenStatus $phpValue,
        int $databaseValue,
    ): void {
        $type = Type::getType(RecoveryTokenStatusType::NAME);
        $this->assertSame($databaseValue, $type->convertToDatabaseValue($phpValue, $this->platform));
    }

    public function invalidDatabaseValues(): array
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

    /**
     * @test
     * @dataProvider invalidDatabaseValues
     * @group doctrine
     */
    public function an_invalid_database_value_causes_an_exception_upon_conversion(mixed $input): void
    {
        $this->expectException(ConversionException::class);

        $type = Type::getType(RecoveryTokenStatusType::NAME);
        $type->convertToPHPValue($input, $this->platform);
    }

    public function validDatabaseValues(): array
    {
        return [
            'active' => ['0', RecoveryTokenStatus::active()],
            'revoked' => ['10', RecoveryTokenStatus::revoked()],
            'forgotten' => ['20', RecoveryTokenStatus::forgotten()],
        ];
    }

    /**
     * @test
     * @dataProvider validDatabaseValues
     * @group doctrine
     *
     * @param mixed $phpValue
     */
    public function a_valid_database_value_is_converted_to_a_sql_value(
        string $databaseValue,
        RecoveryTokenStatus $phpValue,
    ): void {
        $type = Type::getType(RecoveryTokenStatusType::NAME);
        $this->assertTrue($phpValue->equals($type->convertToPHPValue($databaseValue, $this->platform)));
    }
}
