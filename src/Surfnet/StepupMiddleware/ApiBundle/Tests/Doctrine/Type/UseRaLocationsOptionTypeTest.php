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

use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Configuration\Value\UseRaLocationsOption;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\UseRaLocationsOptionType;
use Surfnet\StepupMiddleware\ApiBundle\Tests\TestDataProvider;

class UseRaLocationsOptionTypeTest extends UnitTest
{
    use MockeryPHPUnitIntegration;


    private MariaDBPlatform $platform;

    /**
     * Register the type, since we're forced to use the factory method.
     */
    public static function setUpBeforeClass(): void
    {
        Type::addType(
            UseRaLocationsOptionType::NAME,
            UseRaLocationsOptionType::class,
        );
    }

    public function setUp(): void
    {
        $this->platform = new MariaDBPlatform();
    }

    #[Test]
    #[Group('doctrine')]
    public function a_null_value_remains_null_in_to_sql_conversion(): void
    {
        $configurationInstitution = Type::getType(UseRaLocationsOptionType::NAME);

        $value = $configurationInstitution->convertToDatabaseValue(null, $this->platform);

        $this->assertNull($value);
    }

    #[Test]
    #[DataProviderExternal(TestDataProvider::class, 'notNull')]
    #[Group('doctrine')]
    public function a_value_can_only_be_converted_to_sql_if_it_is_a_use_ra_locations_option_or_null(mixed $incorrectValue,): void
    {
        $this->expectException(ConversionException::class);

        $configurationContactInformation = Type::getType(UseRaLocationsOptionType::NAME);
        $configurationContactInformation->convertToDatabaseValue($incorrectValue, $this->platform);
    }

    #[Test]
    #[Group('doctrine')]
    public function a_non_null_value_is_converted_to_the_correct_format(): void
    {
        $configurationInstitution = Type::getType(UseRaLocationsOptionType::NAME);

        $expected = true;
        $input = new UseRaLocationsOption($expected);
        $output = $configurationInstitution->convertToDatabaseValue($input, $this->platform);

        $this->assertTrue(is_numeric($output));
        $this->assertEquals($expected, $output);
    }

    #[Test]
    #[Group('doctrine')]
    public function a_null_value_remains_null_when_converting_from_db_to_php_value(): void
    {
        $configurationInstitution = Type::getType(UseRaLocationsOptionType::NAME);

        $value = $configurationInstitution->convertToPHPValue(null, $this->platform);

        $this->assertNull($value);
    }

    #[Test]
    #[Group('doctrine')]
    public function a_non_null_value_is_converted_to_a_use_ra_locations_option_value_object(): void
    {
        $configurationInstitution = Type::getType(UseRaLocationsOptionType::NAME);

        $input = true;

        $output = $configurationInstitution->convertToPHPValue($input, $this->platform);

        $this->assertInstanceOf(UseRaLocationsOption::class, $output);
        $this->assertEquals(new UseRaLocationsOption($input), $output);
    }
}
