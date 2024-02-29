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
use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\CommonNameType;

class CommonNameTypeTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MySqlPlatform
     */
    private MariaDBPlatform $platform;

    /**
     * Register the type, since we're forced to use the factory method.
     */
    public static function setUpBeforeClass(): void
    {
        Type::addType(CommonNameType::NAME, CommonNameType::class);
    }

    public function setUp(): void
    {
        $this->platform = new MariaDBPlatform();
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_in_to_sql_conversion(): void
    {
        $commonName = Type::getType(CommonNameType::NAME);

        $value = $commonName->convertToDatabaseValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_the_correct_format(): void
    {
        $commonName = Type::getType(CommonNameType::NAME);

        $input = new CommonName('Arthur Dent');
        $output = $commonName->convertToDatabaseValue($input, $this->platform);

        $this->assertTrue(is_string($output));
        $this->assertEquals('Arthur Dent', $output);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_when_converting_from_db_to_php_value(): void
    {
        $commonName = Type::getType(CommonNameType::NAME);

        $value = $commonName->convertToPHPValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_the_stepup_common_name_object(): void
    {
        $commonName = Type::getType(CommonNameType::NAME);

        $input = 'Arthur Dent';

        $output = $commonName->convertToPHPValue($input, $this->platform);

        $this->assertInstanceOf(CommonName::class, $output);
        $this->assertEquals(new CommonName($input), $output);
    }

    /**
     * @test
     * @group doctrine
     */
    public function an_invalid_database_value_causes_an_exception_upon_conversion(): void
    {
        $this->expectException(ConversionException::class);

        $commonName = Type::getType(CommonNameType::NAME);

        $commonName->convertToPHPValue(false, $this->platform);
    }
}
