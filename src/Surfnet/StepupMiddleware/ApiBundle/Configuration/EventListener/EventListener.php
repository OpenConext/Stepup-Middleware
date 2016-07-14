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

namespace Surfnet\StepupMiddleware\ApiBundle\Configuration\EventListener;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;

abstract class EventListener implements EventListenerInterface
{
    /**
     * @param DomainMessage $domainMessage
     */
    final public function handle(DomainMessage $domainMessage)
    {
        $event  = $domainMessage->getPayload();

        $classParts = explode('\\', get_class($event));
        $method = 'apply' . end($classParts);

        if (!method_exists($this, $method)) {
            return;
        }

        $this->$method($event, $domainMessage);
    }
}
