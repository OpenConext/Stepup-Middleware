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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as UnitTest;
use StdClass;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Value\GssfId;

class GssfIdTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     * @group        domain
     * @dataProvider invalidValueProvider
     */
    public function a_gssf_id_cannot_be_created_with_anything_but_a_nonempty_string(
        string $invalidValue,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new GssfId($invalidValue);
    }

    /**
     * @test
     * @group domain
     */
    public function two_gssf_ids_with_the_same_value_are_equal(): void
    {
        $gssf = new GssfId('a');
        $theSame = new GssfId(' a');
        $different = new GssfId('A');
        $unknown = GssfId::unknown();

        $this->assertTrue($gssf->equals($theSame));
        $this->assertFalse($gssf->equals($different));
        $this->assertFalse($gssf->equals($unknown));
    }

    /**
     * DataProvider for {@see a_gssf_od_cannot_be_created_with_anything_but_a_nonempty_string()}
     */
    public function invalidValueProvider(): array
    {
        return [
            'empty string' => [''],
            'blank string' => ['   '],
        ];
    }
}
