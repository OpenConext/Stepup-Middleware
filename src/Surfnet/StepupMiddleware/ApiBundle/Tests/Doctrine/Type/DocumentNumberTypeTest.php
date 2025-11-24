<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Identity\Value\DocumentNumber;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\DocumentNumberType;
use Surfnet\StepupMiddleware\ApiBundle\Tests\TestDataProvider;
use TypeError;

class DocumentNumberTypeTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    private MariaDBPlatform $platform;

    /**
     * Register the type, since we're forced to use the factory method.
     */
    public static function setUpBeforeClass(): void
    {
        Type::addType(DocumentNumberType::NAME, DocumentNumberType::class);
    }

    public function setUp(): void
    {
        $this->platform = new MariaDBPlatform();
    }

    #[Test]
    #[Group('doctrine')]
    public function a_null_value_remains_null_in_to_sql_conversion(): void
    {
        $type = Type::getType(DocumentNumberType::NAME);

        $value = $type->convertToDatabaseValue(null, $this->platform);

        $this->assertNull($value);
    }

    #[Test]
    #[Group('doctrine')]
    public function a_normal_document_number_is_converted_to_a_database_value(): void
    {
        $type = Type::getType(DocumentNumberType::NAME);

        $input = new DocumentNumber('a');
        $output = $type->convertToDatabaseValue($input, $this->platform);

        $this->assertTrue(is_string($output));
        $this->assertEquals('a', $output);
    }

    #[Test]
    #[Group('doctrine')]
    public function a_null_value_remains_null_when_converting_from_db_to_php_value(): void
    {
        $type = Type::getType(DocumentNumberType::NAME);

        $value = $type->convertToPHPValue(null, $this->platform);

        $this->assertNull($value);
    }

    #[Test]
    #[DataProviderExternal(TestDataProvider::class, 'notNull')]
    #[Group('doctrine')]
    public function a_value_can_only_be_converted_to_sql_if_it_is_a_document_number_or_null(mixed $incorrectValue): void
    {
        $this->expectException(ConversionException::class);

        $configurationContactInformation = Type::getType(DocumentNumberType::NAME);
        $configurationContactInformation->convertToDatabaseValue($incorrectValue, $this->platform);
    }

    #[Test]
    #[Group('doctrine')]
    public function a_non_null_value_is_converted_to_the_stepup_document_number_object(): void
    {
        $type = Type::getType(DocumentNumberType::NAME);

        $input = '12345';
        $output = $type->convertToPHPValue($input, $this->platform);

        $this->assertInstanceOf(DocumentNumber::class, $output);
        $this->assertTrue((new DocumentNumber($input))->equals($output));
    }

    #[Test]
    #[Group('doctrine')]
    public function an_invalid_database_value_causes_an_exception_upon_conversion(): void
    {
        $this->expectException(TypeError::class);

        $type = Type::getType(DocumentNumberType::NAME);

        $input = 12345;

        $type->convertToPHPValue($input, $this->platform);
    }
}
