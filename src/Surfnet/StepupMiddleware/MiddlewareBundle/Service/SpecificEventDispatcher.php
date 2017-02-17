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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Service;

use Exception;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\DBALEventHydrator;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\EventCollection;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\ProjectorCollection;

final class SpecificEventDispatcher
{
    /**
     * @var DBALConnectionHelper
     */
    private $connectionHelper;

    /**
     * @var DBALEventHydrator
     */
    private $eventHydrator;

    public function __construct(DBALConnectionHelper $connectionHelper, DBALEventHydrator $eventHydrator)
    {
        $this->connectionHelper = $connectionHelper;
        $this->eventHydrator = $eventHydrator;
    }

    public function dispatchEventsForProjectors(
        EventCollection $eventCollection,
        ProjectorCollection $projectorCollection
    ) {
        $this->connectionHelper->beginTransaction();

        try {
            $events = $this->eventHydrator->getEventsFrom($eventCollection);

            foreach ($projectorCollection as $projector) {
                foreach ($events as $event) {
                    $projector->handle($event);
                }
            }

            $this->connectionHelper->commit();
        } catch (Exception $exception) {
            $this->connectionHelper->rollBack();
        }
    }
}
