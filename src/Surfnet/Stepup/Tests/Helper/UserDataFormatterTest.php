<?php

/**
 * Copyright 2022 SURFnet B.V.
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

use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\Helper\UserDataFormatter;

class UserDataFormatterTest extends TestCase
{
    public function test_data_is_formatted(): void
    {
        $formatter = new UserDataFormatter('Stepup-Middleware');
        $expected = [
            'status' => 'OK',
            'data' => [
                ['name' => 'name1', 'value' => 'some-value-1'],
                ['name' => 'name2', 'value' => 'some-value-2'],
                ['name' => 'name3', 'value' => 'some-value-3'],
            ],
            'name' => 'Stepup-Middleware'
        ];

        $inputData = [
            'foobar-name1' => 'some-value-1',
            'foobar-name2' => 'some-value-2',
            'foobar-name3' => 'some-value-3',
        ];

        $this->assertEquals($expected, $formatter->format($inputData, []));
    }

    public function test_errors_are_included_in_output(): void
    {
        $formatter = new UserDataFormatter('Stepup-Middleware');
        $expected = [
            'status' => 'FAILED',
            'data' => [
                ['name' => 'name1', 'value' => 'some-value-1'],
            ],
            'name' => 'Stepup-Middleware',
            'message' => [
                'The application is teetering on the edge of catastrophe!'
            ]
        ];

        $inputData = [
            'foobar-name1' => 'some-value-1',
        ];

        $this->assertEquals($expected,
            $formatter->format(
                $inputData,
                ['The application is teetering on the edge of catastrophe!']
            )
        );
    }
}
