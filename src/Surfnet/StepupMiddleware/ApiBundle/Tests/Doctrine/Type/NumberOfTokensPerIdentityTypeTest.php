<?php

/**
 * Copyright 2018 SURFnet B.V.
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
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\NumberOfTokensPerIdentityType;

class NumberOfTokensPerIdentityTypeTest extends UnitTest
{
    use MockeryPHPUnitIntegration;


    private MariaDBPlatform $platform;

    /**
     * Register the type, since we're forced to use the factory method.
     */
    public static function setUpBeforeClass(): void
    {
        Type::addType(
            NumberOfTokensPerIdentityType::NAME,
            NumberOfTokensPerIdentityType::class,
        );
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
        $numberOfTokensPerIdentity = Type::getType(NumberOfTokensPerIdentityType::NAME);

        $value = $numberOfTokensPerIdentity->convertToDatabaseValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     *
     * @dataProvider \Surfnet\StepupMiddleware\ApiBundle\Tests\TestDataProvider::notNull
     */
    public function a_value_can_only_be_converted_to_sql_if_it_is_an_option_type_or_null(mixed $incorrectValue): void
    {
        $this->expectException(ConversionException::class);

        $numberOfTokensPerIdentity = Type::getType(NumberOfTokensPerIdentityType::NAME);
        $numberOfTokensPerIdentity->convertToDatabaseValue($incorrectValue, $this->platform);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_the_correct_format(): void
    {
        $numberOfTokensPerIdentity = Type::getType(NumberOfTokensPerIdentityType::NAME);

        $expected = 4;
        $input = new NumberOfTokensPerIdentityOption($expected);
        $output = $numberOfTokensPerIdentity->convertToDatabaseValue($input, $this->platform);

        $this->assertTrue(is_numeric($output));
        $this->assertEquals($expected, $output);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_when_converting_from_db_to_php_value(): void
    {
        $numberOfTokensPerIdentity = Type::getType(NumberOfTokensPerIdentityType::NAME);

        $value = $numberOfTokensPerIdentity->convertToPHPValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_an_option_value_object(): void
    {
        $numberOfTokensPerIdentity = Type::getType(NumberOfTokensPerIdentityType::NAME);

        $input = 2;

        $output = $numberOfTokensPerIdentity->convertToPHPValue($input, $this->platform);

        $this->assertInstanceOf(NumberOfTokensPerIdentityOption::class, $output);
        $this->assertEquals(new NumberOfTokensPerIdentityOption(2), $output);
    }
}
