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

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\SecondFactorStatusType;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\SecondFactorStatus;

class SecondFactorStatusTypeTest extends UnitTest
{
    /**
     * @var \Doctrine\DBAL\Platforms\MySqlPlatform
     */
    private $platform;

    /**
     * Register the type, since we're forced to use the factory method.
     */
    public static function setUpBeforeClass(): void
    {
        Type::addType(SecondFactorStatusType::NAME, 'Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\SecondFactorStatusType');
    }

    public function setUp(): void
    {
        $this->platform = new MySqlPlatform();
    }

    public function invalidPhpValues()
    {
        return [
            'null' => [null],
            'string' => ['string'],
            'int' => [9],
            'float' => [9.1],
            'array' => [array()],
            'object of a different type' => [new \stdClass],
            'resource' => [fopen('php://memory', 'w')],
        ];
    }

    /**
     * @test
     * @dataProvider invalidPhpValues
     * @group doctrine
     *
     * @param mixed $value
     */
    public function an_invalid_php_value_is_not_accepted_in_to_sql_conversion($value)
    {
        $this->expectException(\Doctrine\DBAL\Types\ConversionException::class);

        $type = Type::getType(SecondFactorStatusType::NAME);
        $type->convertToDatabaseValue($value, $this->platform);
    }

    public function validPhpValues()
    {
        return [
            'unverified' => [SecondFactorStatus::unverified(), 0],
            'verified' => [SecondFactorStatus::verified(), 10],
            'vetted' => [SecondFactorStatus::vetted(), 20],
            'revoked' => [SecondFactorStatus::revoked(), 30],
        ];
    }

    /**
     * @test
     * @dataProvider validPhpValues
     * @group doctrine
     *
     * @param mixed $phpValue
     * @param int $databaseValue
     */
    public function a_valid_php_value_is_converted_to_a_sql_value($phpValue, $databaseValue)
    {
        $type = Type::getType(SecondFactorStatusType::NAME);
        $this->assertSame($databaseValue, $type->convertToDatabaseValue($phpValue, $this->platform));
    }

    public function invalidDatabaseValues()
    {
        return [
            'null' => [null],
            'invalid string' => ['string'],
            'int' => [9],
            'float' => [9.1],
            'array' => [array()],
            'object of a different type' => [new \stdClass],
            'resource' => [fopen('php://memory', 'w')],
        ];
    }

    /**
     * @test
     * @dataProvider invalidDatabaseValues
     * @group doctrine
     *
     * @param mixed $input
     */
    public function an_invalid_database_value_causes_an_exception_upon_conversion($input)
    {
        $this->expectException(\Doctrine\DBAL\Types\ConversionException::class);

        $type = Type::getType(SecondFactorStatusType::NAME);
        $type->convertToPHPValue($input, $this->platform);
    }

    public function validDatabaseValues()
    {
        return [
            'unverified' => ['0', SecondFactorStatus::unverified()],
            'verified' => ['10', SecondFactorStatus::verified()],
            'vetted' => ['20', SecondFactorStatus::vetted()],
            'revoked' => ['30', SecondFactorStatus::revoked()],
        ];
    }

    /**
     * @test
     * @dataProvider validDatabaseValues
     * @group doctrine
     *
     * @param int $databaseValue
     * @param mixed $phpValue
     */
    public function a_valid_database_value_is_converted_to_a_sql_value($databaseValue, $phpValue)
    {
        $type = Type::getType(SecondFactorStatusType::NAME);
        $this->assertTrue($phpValue->equals($type->convertToPHPValue($databaseValue, $this->platform)));
    }
}
