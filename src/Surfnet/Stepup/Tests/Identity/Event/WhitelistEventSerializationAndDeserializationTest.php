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

namespace Surfnet\Stepup\Tests\Identity\Event;

use Broadway\Serializer\Serializable as SerializableInterface;
use PHPUnit\Framework\TestCase as UnitTest;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Event\InstitutionsAddedToWhitelistEvent;
use Surfnet\Stepup\Identity\Event\InstitutionsRemovedFromWhitelistEvent;
use Surfnet\Stepup\Identity\Event\WhitelistCreatedEvent;
use Surfnet\Stepup\Identity\Event\WhitelistReplacedEvent;
use Surfnet\Stepup\Identity\Value\Institution;

class WhitelistEventSerializationAndDeserializationTest extends UnitTest
{
    /**
     * @test
     * @group        domain
     * @group        whitelist
     * @dataProvider eventProvider
     */
    public function an_event_should_be_the_same_after_serialization_and_deserialization(SerializableInterface $event,): void
    {
        $class = $event::class;
        $this->assertTrue($event == call_user_func([$class, 'deserialize'], $event->serialize()));
    }

    public function eventProvider(): array
    {
        return [
            'WhitelistCreatedEvent' => [
                new WhitelistCreatedEvent($this->getInstitutionCollection()),
            ],
            'WhitelistReplacedEvent' => [
                new WhitelistReplacedEvent($this->getInstitutionCollection()),
            ],
            'InstitutionsAddedToWhitelistEvent' => [
                new InstitutionsAddedToWhitelistEvent($this->getInstitutionCollection()),
            ],
            'InstitutionsRemovedFromWhitelistEvent' => [
                new InstitutionsRemovedFromWhitelistEvent($this->getInstitutionCollection()),
            ],
        ];
    }

    /**
     * @return InstitutionCollection
     */
    private function getInstitutionCollection()
    {
        static $institutionCollection;

        if ($institutionCollection === null) {
            $institutionCollection = new InstitutionCollection([
                new Institution('Babelfish Inc.'),
                new Institution('The Blue Note'),
                new Institution('SURFnet'),
                new Institution('Ibuildings'),
            ]);
        }

        return $institutionCollection;
    }
}
