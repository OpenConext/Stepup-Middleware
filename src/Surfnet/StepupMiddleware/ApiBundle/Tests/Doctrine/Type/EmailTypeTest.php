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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Identity\Value\Email;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\EmailType;

class EmailTypeTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    private MariaDBPlatform $platform;

    /**
     * Register the type, since we're forced to use the factory method.
     */
    public static function setUpBeforeClass(): void
    {
        Type::addType(EmailType::NAME, EmailType::class);
    }

    public function setUp(): void
    {
        $this->platform = new MariaDBPlatform();
    }

    #[Test]
    #[Group('doctrine')]
    public function a_null_value_remains_null_in_to_sql_conversion(): void
    {
        $email = Type::getType(EmailType::NAME);

        $value = $email->convertToDatabaseValue(null, $this->platform);

        $this->assertNull($value);
    }

    #[Test]
    #[Group('doctrine')]
    public function a_non_null_value_is_converted_to_the_correct_format(): void
    {
        $email = Type::getType(EmailType::NAME);

        $input = new Email('arthur@babelfish.invalid');
        $output = $email->convertToDatabaseValue($input, $this->platform);

        $this->assertTrue(is_string($output));
        $this->assertEquals('arthur@babelfish.invalid', $output);
    }

    #[Test]
    #[Group('doctrine')]
    public function a_null_value_remains_null_when_converting_from_db_to_php_value(): void
    {
        $email = Type::getType(EmailType::NAME);

        $value = $email->convertToPHPValue(null, $this->platform);

        $this->assertNull($value);
    }

    #[Test]
    #[Group('doctrine')]
    public function a_non_null_value_is_converted_to_the_stepup_email_object(): void
    {
        $email = Type::getType(EmailType::NAME);

        $input = 'arthur@babelfish.invalid';

        $output = $email->convertToPHPValue($input, $this->platform);

        $this->assertInstanceOf(Email::class, $output);
        $this->assertEquals(new Email($input), $output);
    }

    #[Test]
    #[Group('doctrine')]
    public function an_invalid_database_value_causes_an_exception_upon_conversion(): void
    {
        $this->expectException(ConversionException::class);

        $email = Type::getType(EmailType::NAME);

        $email->convertToPHPValue(false, $this->platform);
    }
}
