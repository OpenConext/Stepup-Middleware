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

namespace Surfnet\Stepup\Tests\Helper;

use PHPUnit\Framework\TestCase as TestCase;
use StdClass;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Exception\JsonException;
use Surfnet\Stepup\Helper\JsonHelper;

class JsonHelperTest extends TestCase
{
    /**
     * @test
     * @group json
     *
     * @dataProvider nonStringProvider
     * @param $nonString
     */
    public function json_helper_can_only_decode_strings(bool|int|float|StdClass|array $nonString): void
    {
        $this->expectException(InvalidArgumentException::class);

        JsonHelper::decode($nonString);
    }

    /**
     * @test
     * @group json
     */
    public function json_helper_decodes_strings_to_arrays(): void
    {
        $expectedDecodedResult = ['hello' => 'world'];
        $json = '{ "hello" : "world" }';

        $actualDecodedResult = JsonHelper::decode($json);

        $this->assertSame($expectedDecodedResult, $actualDecodedResult);
    }

    /**
     * @test
     * @group json
     */
    public function json_helper_throws_an_exception_when_there_is_a_syntax_error(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        $jsonWithMissingDoubleQuotes = '{ hello : world }';

        JsonHelper::decode($jsonWithMissingDoubleQuotes);
    }

    public function nonStringProvider(): array
    {
        return [
            'boolean' => [true],
            'array' => [[]],
            'integer' => [1],
            'float' => [1.2],
            'object' => [new StdClass()],
        ];
    }
}
