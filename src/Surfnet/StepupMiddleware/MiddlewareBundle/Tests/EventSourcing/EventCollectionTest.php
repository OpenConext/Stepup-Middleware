<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Tests\EventSourcing;

use PHPUnit_Framework_TestCase as TestCase;
use stdClass;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\EventCollection;
use Surfnet\StepupMiddleware\MiddlewareBundle\Exception\InvalidArgumentException;

class EventCollectionTest extends TestCase
{
    /**
     * @test
     * @group event-replay
     *
     * @dataProvider emptyOrNonStringProvider
     * @param $emptyOrNonString
     */
    public function an_event_collection_must_be_created_from_an_array_of_non_empty_strings($emptyOrNonString)
    {
        $this->setExpectedException(
            InvalidArgumentException::class,
            'Invalid argument type: "non-empty string" expected'
        );

        new EventCollection([$emptyOrNonString]);
    }

    /**
     * @test
     * @group event-replay
     */
    public function an_event_collection_must_contain_event_names_that_are_existing_class_names()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'does not exist');

        $nonExistantClass = 'This\Class\Does\Not\Exist';

        new EventCollection([$nonExistantClass]);
    }

    public function emptyOrNonStringProvider()
    {
        return [
            'null' => [null],
            'boolean' => [true],
            'integer' => [1],
            'float' => [123],
            'empty string' => [''],
            'object' => [new stdClass()],
            'array' => [[]]
        ];
    }
}
