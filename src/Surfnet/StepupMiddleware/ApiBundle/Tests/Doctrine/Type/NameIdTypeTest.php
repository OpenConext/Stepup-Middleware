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
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\NameIdType;

class NameIdTypeTest extends UnitTest
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
        Type::addType(NameIdType::NAME, 'Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\NameIdType');
    }

    public function setUp(): void
    {
        $this->platform = new MySqlPlatform();
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_in_to_sql_conversion()
    {
        $nameId = Type::getType(NameIdType::NAME);

        $value = $nameId->convertToDatabaseValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_the_correct_format()
    {
        $nameId = Type::getType(NameIdType::NAME);

        $expected = md5('someNameId');
        $input  = new NameId($expected);
        $output = $nameId->convertToDatabaseValue($input, $this->platform);

        $this->assertTrue(is_string($output));
        $this->assertEquals($expected, $output);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_when_converting_from_db_to_php_value()
    {
        $nameId = Type::getType(NameIdType::NAME);

        $value = $nameId->convertToPHPValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_a_name_id_value_object()
    {
        $nameId = Type::getType(NameIdType::NAME);

        $input = md5('someNameId');

        $output = $nameId->convertToPHPValue($input, $this->platform);

        $this->assertInstanceOf('Surfnet\Stepup\Identity\Value\NameId', $output);
        $this->assertEquals(new NameId($input), $output);
    }

    /**
     * @test
     * @group doctrine
     */
    public function an_invalid_database_value_causes_an_exception_upon_conversion()
    {
        $this->expectException(\Doctrine\DBAL\Types\ConversionException::class);

        $nameId = Type::getType(NameIdType::NAME);

        $nameId->convertToPHPValue(false, $this->platform);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_excessive_long_database_value_causes_an_exception_upon_conversion()
    {
        $this->expectException(\Doctrine\DBAL\Types\ConversionException::class);

        $nameId = Type::getType(NameIdType::NAME);
        // the bin2hex openssle random bytes combination creates a string of 256 characters long.
        // Exceeding the limit of 255 characters by one
        $nameId->convertToPHPValue(bin2hex(openssl_random_pseudo_bytes(128)), $this->platform);
    }
}
