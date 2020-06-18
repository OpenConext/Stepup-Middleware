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
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;

class YubikeyPublicIdTest extends UnitTest
{
    /**
     * @test
     * @group domain
     */
    public function two_yubikey_public_ids_with_the_same_value_are_equal()
    {
        $id          = new YubikeyPublicId('00001234');
        $theSame     = new YubikeyPublicId('00001234');
        $different   = new YubikeyPublicId('987654321');
        $unknown     = YubikeyPublicId::unknown();

        $this->assertTrue($id->equals($theSame));
        $this->assertFalse($id->equals($different));
        $this->assertFalse($id->equals($unknown));
    }

    public function invalidFormatProvider()
    {
        return [
            '7-character unpadded ID'           => ['1906381'],
            '9-character padded ID'             => ['0123456789'],
            '19-character padded ID'            => ['01234567890123456789'],
            '21-character ID'                   => ['101234567890123456789'],
            'empty ID'                          => [''],
            'ID with alphabetical characters'   => ['abc'],
            'ID with alphanumerical characters' => ['abc01908389'],
            'Larger than 0xffffffffffffffff'    => ['18446744073709551616']
        ];
    }

    /**
     * @test
     * @group domain
     * @dataProvider invalidFormatProvider
     *
     * @param mixed $invalidFormat
     */
    public function it_cannot_be_constructed_with_an_invalid_format($invalidFormat)
    {
        $this->expectException(\Surfnet\Stepup\Exception\InvalidArgumentException::class);

        new YubikeyPublicId($invalidFormat);
    }

    public function validFormatProvider()
    {
        return [
            '8-character ID'  => ['01906381'],
            '1-character ID'  => ['00000001'],
            '0-character ID'  => ['00000000'],
            '16-character ID' => ['1234560123456789'],
            '20-character ID' => ['12345678901234567890'],
        ];
    }

    /**
     * @test
     * @group domain
     * @dataProvider validFormatProvider
     *
     * @param string $validFormat
     */
    public function its_value_matches_its_input_value($validFormat)
    {
        $id = new YubikeyPublicId($validFormat);

        $this->assertEquals($validFormat, $id->getValue());
    }
}
