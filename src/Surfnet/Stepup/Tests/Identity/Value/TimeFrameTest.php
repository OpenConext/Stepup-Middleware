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

namespace Surfnet\Stepup\Tests\Identity\Value;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as UnitTest;
use StdClass;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Value\TimeFrame;
use Throwable;
use TypeError;

class TimeFrameTest extends UnitTest
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     * @group        domain
     * @dataProvider invalidValueProviderInt
     */
    public function it_cannot_be_given_an_non_positive_amount_of_seconds(int $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);

        TimeFrame::ofSeconds($invalidValue);
    }

    /**
     * @test
     * @group        domain
     * @dataProvider invalidValueProviderOtherTypes
     */
    public function it_cannot_be_given_an_non_positive_amount_of_secondsOtherTypes(string|float|StdClass|array $invalidValue): void
    {
        $this->expectException(TypeError::class);

        TimeFrame::ofSeconds($invalidValue);
    }

    /**
     * @test
     * @group domain
     */
    public function to_string_output_matches_amount_of_seconds_as_string(): void
    {
        $seconds = 1000;

        $timeFrame = TimeFrame::ofSeconds($seconds);

        $this->assertEquals(
            '1000',
            (string)$timeFrame,
            'The amount of seconds as string must match timeFrame::__toString',
        );
    }

    /**
     * dataprovider
     */
    public function invalidValueProviderInt(): array
    {
        return [
            'zero' => [0],
            'negative int' => [-1],
        ];
    }

    /**
     * dataprovider
     */
    public function invalidValueProviderOtherTypes(): array
    {
        return [
            'empty string' => [''],
            'string' => ['abc'],
            'array' => [[]],
            'float' => [2.123],
            'object' => [new StdClass()],
        ];
    }
}
