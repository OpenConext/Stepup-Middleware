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
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\ConfigurationContactInformationType;

class ConfigurationContactInformationTypeTest extends UnitTest
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
            ConfigurationContactInformationType::NAME,
            ConfigurationContactInformationType::class,
        );
    }

    public function setUp(): void
    {
        $this->platform = new MySqlPlatform();
    }

    /**
     * @test
     * @group doctrine
     *
     * @dataProvider \Surfnet\StepupMiddleware\ApiBundle\Tests\TestDataProvider::notNull
     * @param $incorrectValue
     */
    public function a_value_can_only_be_converted_to_sql_if_it_is_contact_information_or_null($incorrectValue): void
    {
        $this->expectException(ConversionException::class);

        $configurationContactInformation = Type::getType(ConfigurationContactInformationType::NAME);
        $configurationContactInformation->convertToDatabaseValue($incorrectValue, $this->platform);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_in_to_sql_conversion(): void
    {
        $configurationContactInformation = Type::getType(ConfigurationContactInformationType::NAME);

        $value = $configurationContactInformation->convertToDatabaseValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_the_correct_format(): void
    {
        $configurationContactInformation = Type::getType(ConfigurationContactInformationType::NAME);

        $expected = 'Call me maybe';
        $input = new ContactInformation($expected);
        $output = $configurationContactInformation->convertToDatabaseValue($input, $this->platform);

        $this->assertTrue(is_string($output));
        $this->assertEquals($expected, $output);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_when_converting_from_db_to_php_value(): void
    {
        $configurationContactInformation = Type::getType(ConfigurationContactInformationType::NAME);

        $value = $configurationContactInformation->convertToPHPValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_a_contact_information_value_object(): void
    {
        $configurationContactInformation = Type::getType(ConfigurationContactInformationType::NAME);

        $input = 'Call me Maybe';

        $output = $configurationContactInformation->convertToPHPValue($input, $this->platform);

        $this->assertInstanceOf(ContactInformation::class, $output);
        $this->assertEquals(new ContactInformation($input), $output);
    }

    /**
     * @test
     * @group doctrine
     */
    public function an_invalid_database_value_causes_an_exception_upon_conversion(): void
    {
        $this->expectException(ConversionException::class);

        $configurationContactInformation = Type::getType(ConfigurationContactInformationType::NAME);

        $configurationContactInformation->convertToPHPValue(false, $this->platform);
    }
}
