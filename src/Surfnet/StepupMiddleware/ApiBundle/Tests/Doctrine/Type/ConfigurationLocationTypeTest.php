<?php

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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Doctrine\Type;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\ConfigurationLocationType;

class ConfigurationLocationTypeTest extends UnitTest
{
    /**
     * @var MySqlPlatform
     */
    private MySqlPlatform $platform;

    /**
     * Register the type, since we're forced to use the factory method.
     */
    public static function setUpBeforeClass(): void
    {
        Type::addType(
            ConfigurationLocationType::NAME,
            ConfigurationLocationType::class,
        );
    }

    public function setUp(): void
    {
        $this->platform = new MySqlPlatform();
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_in_to_sql_conversion(): void
    {
        $configurationLocation = Type::getType(ConfigurationLocationType::NAME);

        $value = $configurationLocation->convertToDatabaseValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     *
     * @dataProvider \Surfnet\StepupMiddleware\ApiBundle\Tests\TestDataProvider::notNull
     * @param $incorrectValue
     */
    public function a_value_can_only_be_converted_to_sql_if_it_is_a_location_or_null($incorrectValue): void
    {
        $this->expectException(ConversionException::class);

        $configurationContactInformation = Type::getType(ConfigurationLocationType::NAME);
        $configurationContactInformation->convertToDatabaseValue($incorrectValue, $this->platform);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_the_correct_format(): void
    {
        $configurationLocation = Type::getType(ConfigurationLocationType::NAME);

        $expected = 'Somewhere behind you';
        $input = new Location($expected);
        $output = $configurationLocation->convertToDatabaseValue($input, $this->platform);

        $this->assertTrue(is_string($output));
        $this->assertEquals($expected, $output);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_when_converting_from_db_to_php_value(): void
    {
        $configurationLocation = Type::getType(ConfigurationLocationType::NAME);

        $value = $configurationLocation->convertToPHPValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_a_configuration_location_value_object(): void
    {
        $configurationLocation = Type::getType(ConfigurationLocationType::NAME);

        $input = 'Call me Maybe';

        $output = $configurationLocation->convertToPHPValue($input, $this->platform);

        $this->assertInstanceOf(Location::class, $output);
        $this->assertEquals(new Location($input), $output);
    }

    /**
     * @test
     * @group doctrine
     */
    public function an_invalid_database_value_causes_an_exception_upon_conversion(): void
    {
        $this->expectException(ConversionException::class);

        $configurationLocation = Type::getType(ConfigurationLocationType::NAME);

        $configurationLocation->convertToPHPValue(false, $this->platform);
    }
}
