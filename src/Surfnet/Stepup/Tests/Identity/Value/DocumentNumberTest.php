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

namespace Surfnet\Stepup\Tests\Identity\Value;

use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Value\DocumentNumber;

class DocumentNumberTest extends UnitTest
{
    /**
     * @test
     * @group        domain
     * @dataProvider validDocumentNumberProvider
     *
     * @param string $documentNumber
     */
    public function the_document_number_must_be_valid(string $documentNumber): void
    {
        $document = new DocumentNumber($documentNumber);
        $this->assertInstanceOf(DocumentNumber::class, $document);
    }


    /**
     * @test
     * @group        domain
     * @dataProvider invalidDocumentNumberProvider
     *
     * @param string $invalidValue
     */
    public function the_document_number_must_not_contain_illegal_characters(string $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DocumentNumber($invalidValue);
    }


    /**
     * @test
     * @group        domain
     */
    public function the_document_number_must_be_a_non_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DocumentNumber('');
    }


    /**
     * @test
     * @group domain
     */
    public function two_document_numbers_with_the_same_value_are_equal(): void
    {
        $commonName = new DocumentNumber('JHA1B4');
        $theSame    = new DocumentNumber('JHA1B4');
        $different  = new DocumentNumber('IGZ0A3');
        $unknown    = DocumentNumber::unknown();

        $this->assertTrue($commonName->equals($theSame));
        $this->assertFalse($commonName->equals($different));
        $this->assertFalse($commonName->equals($unknown));
    }


    /**
     * provider for {@see the_document_number_address_must_not_contain_illegal_characters()}
     */
    public function invalidDocumentNumberProvider(): array
    {
        return [
            'Illegal character' => ['#12345'],
            'Too long'          => ['TooLong'],
            'Too short'         => ['Shor'], // Short
            'Contains space'    => ['AB 123'],
        ];
    }


    /**
     * provider for {@see the_document_number_address_must_be_valid()}
     */
    public function validDocumentNumberProvider(): array
    {
        return [
            'Single hyphen'    => ['-'],
            'Contains hyphen'  => ['123-45'],
            'Unknown document' => ['–'],
            'Uppercase'        => ['A1B2C3'],
            'Lowercase'        => ['a2b2c3'],
            'Mixed case'       => ['a2B2c3'],
        ];
    }
}
