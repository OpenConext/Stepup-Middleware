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

use DateTime as CoreDateTime;
use DateTimeZone;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit_Framework_TestCase as UnitTest;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\DateTimeType;

class DateTimeTypeTest extends UnitTest
{
    /**
     * @var \Doctrine\DBAL\Platforms\MySqlPlatform
     */
    private $platform;

    /**
     * Register the type, since we're forced to use the factory method.
     */
    public static function setUpBeforeClass()
    {
        Type::addType(DateTimeType::NAME, 'Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\DateTimeType');
    }

    public function setUp()
    {
        $this->platform = new MySqlPlatform();
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_in_to_sql_conversion()
    {
        $dateTime = Type::getType(DateTimeType::NAME);

        $value = $dateTime->convertToDatabaseValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_the_correct_format()
    {
        $dateTime = Type::getType(DateTimeType::NAME);

        $input = new DateTime(new CoreDateTime('@0'));
        $output = $dateTime->convertToDatabaseValue($input, $this->platform);

        $this->assertTrue(is_string($output));
        $this->assertEquals('1970-01-01 00:00:00', $output);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_when_converting_from_db_to_php_value()
    {
        $dateTime = Type::getType(DateTimeType::NAME);

        $value = $dateTime->convertToPHPValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_the_stepup_datetime_object()
    {
        $dateTime = Type::getType(DateTimeType::NAME);

        $input = '2015-02-17 10:48:22';
        $inputAsCoreDateTime = CoreDateTime::createFromFormat(
            'Y-m-d H:i:s',
            '2015-02-17 10:48:22',
            new DateTimeZone('UTC')
        );

        $output = $dateTime->convertToPHPValue($input, $this->platform);

        $this->assertInstanceOf('Surfnet\Stepup\DateTime\DateTime', $output);
        $this->assertEquals(new DateTime($inputAsCoreDateTime), $output);
    }

    /**
     * @test
     * @group doctrine
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function an_invalid_database_value_causes_an_exception_upon_conversion()
    {
        $dateTime = Type::getType(DateTimeType::NAME);

        $input = 'This is an invalid formatted datetime';

        $dateTime->convertToPHPValue($input, $this->platform);
    }
}
