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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Surfnet\Stepup\Configuration\Event\NewConfigurationCreatedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\EventCollection;
use Surfnet\StepupMiddleware\MiddlewareBundle\Exception\InvalidArgumentException;

class EventCollectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    #[DataProvider('emptyOrNonStringProvider')]
    #[Group('event-replay')]
    public function an_event_collection_must_be_created_from_an_array_of_non_empty_strings(
        bool|int|string|stdClass|array|null $emptyOrNonString,
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument type: "non-empty string" expected');

        new EventCollection([$emptyOrNonString]); // @phpstan-ignore-line argument.type: Warning about a faulty constructor argument is exactly what we are testing here
    }

    #[Test]
    #[Group('event-replay')]
    public function an_event_collection_must_contain_event_names_that_are_existing_class_names(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $nonExistantClass = 'This\Class\Does\Not\Exist';

        new EventCollection([$nonExistantClass]);
    }

    #[Test]
    #[Group('event-replay')]
    public function an_event_collection_contains_given_event_names(): void
    {
        $eventCollection = new EventCollection([NewConfigurationCreatedEvent::class]);

        $this->assertTrue(
            $eventCollection->contains(NewConfigurationCreatedEvent::class),
            'EventCollection should contain NewConfigurationCreatedEvent but it does not',
        );
    }

    #[Test]
    #[Group('event-replay')]
    public function event_names_can_be_retrieved_from_an_event_collection(): void
    {
        $eventNames = [NewConfigurationCreatedEvent::class];
        $eventCollection = new EventCollection($eventNames);

        $actualEventNames = $eventCollection->getEventNames();

        $this->assertSame(
            $eventNames,
            $actualEventNames,
            'Event names cannot be correctly retrieved from an EventCollection',
        );
    }

    #[Test]
    #[Group('event-replay')]
    public function an_event_collection_does_not_contain_given_event_names(): void
    {
        $eventCollection = new EventCollection([SecondFactorVettedEvent::class]);

        $this->assertFalse(
            $eventCollection->contains(NewConfigurationCreatedEvent::class),
            'EventCollection should not contain NewConfigurationCreatedEvent but it does',
        );
    }

    #[Test]
    #[Group('event-replay')]
    public function a_subset_of_events_can_be_selected_from_an_event_collection(): void
    {
        $eventCollection = new EventCollection([NewConfigurationCreatedEvent::class, SecondFactorVettedEvent::class]);

        $subset = $eventCollection->select([NewConfigurationCreatedEvent::class]);

        $this->assertTrue(
            $subset->contains(NewConfigurationCreatedEvent::class),
            'EventCollection subset should contain NewConfigurationCreatedEvent but it did not',
        );
    }

    #[Test]
    #[Group('event-replay')]
    public function a_subset_containing_events_not_present_in_the_event_collection_cannot_be_selected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subset of event names contains event names not present in collection');

        $eventCollection = new EventCollection([NewConfigurationCreatedEvent::class]);
        $eventCollection->select([SecondFactorVettedEvent::class]);
    }

    #[Test]
    #[Group('event-replay')]
    public function events_in_an_event_collection_can_be_formatted_as_event_stream_compatible_event_types(): void
    {
        $eventCollection = new EventCollection([NewConfigurationCreatedEvent::class, SecondFactorVettedEvent::class]);

        $expectedEventTypes = [
            'Surfnet.Stepup.Configuration.Event.NewConfigurationCreatedEvent',
            'Surfnet.Stepup.Identity.Event.SecondFactorVettedEvent',
        ];
        $actualEventTypes = $eventCollection->formatAsEventStreamTypes();

        $this->assertEquals(
            $expectedEventTypes,
            $actualEventTypes,
            'The events in the event collection should have been formatted as event stream compatible event types but they have not',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyOrNonStringProvider(): array
    {
        return [
            'null' => [null],
            'boolean' => [true],
            'integer' => [1],
            'float' => [123],
            'empty string' => [''],
            'object' => [new stdClass()],
            'array' => [[]],
        ];
    }
}
