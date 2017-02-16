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
use Surfnet\Stepup\Configuration\Event\NewConfigurationCreatedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
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

    /**
     * @test
     * @group event-replay
     */
    public function an_event_collection_contains_given_event_names()
    {
        $eventCollection = new EventCollection([NewConfigurationCreatedEvent::class, SecondFactorVettedEvent::class]);

        $this->assertTrue(
            $eventCollection->contains(NewConfigurationCreatedEvent::class),
            'EventCollection should contain NewConfigurationCreatedEvent but it does not'
        );
        $this->assertTrue(
            $eventCollection->contains(SecondFactorVettedEvent::class),
            'EventCollection should contain SecondFactorVettedEvent but it does not'
        );
    }

    /**
     * @test
     * @group event-replay
     */
    public function an_event_collection_does_not_contain_given_event_names()
    {
        $eventCollection = new EventCollection([SecondFactorVettedEvent::class]);

        $this->assertFalse(
            $eventCollection->contains(NewConfigurationCreatedEvent::class),
            'EventCollection should not contain NewConfigurationCreatedEvent but it does'
        );
    }

    /**
     * @test
     * @group event-replay
     */
    public function a_subset_of_events_can_be_selected_from_an_event_collection()
    {
        $eventCollection = new EventCollection([NewConfigurationCreatedEvent::class, SecondFactorVettedEvent::class]);

        $subset = $eventCollection->select([NewConfigurationCreatedEvent::class]);

        $this->assertTrue(
            $subset->contains(NewConfigurationCreatedEvent::class),
            'EventCollection subset should contain NewConfigurationCreatedEvent but it did not'
        );
    }

    /**
     * @test
     * @group event-replay
     */
    public function a_subset_containing_events_not_present_in_the_event_collection_cannot_be_selected()
    {
        $this->setExpectedException(
            InvalidArgumentException::class,
            'Subset of event names contains event names not present in collection'
        );

        $eventCollection = new EventCollection([NewConfigurationCreatedEvent::class]);
        $eventCollection->select([SecondFactorVettedEvent::class]);
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
