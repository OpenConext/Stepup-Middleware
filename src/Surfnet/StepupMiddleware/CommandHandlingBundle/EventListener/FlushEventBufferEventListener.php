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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\EventListener;

use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class FlushEventBufferEventListener implements EventSubscriberInterface
{
    /**
     * @var BufferedEventBus
     */
    private $eventBus;

    /**
     * @param BufferedEventBus $eventBus
     */
    public function __construct(BufferedEventBus $eventBus)
    {
        $this->eventBus = $eventBus;
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this->eventBus->flush();
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::TERMINATE => ['onKernelTerminate', 100]];
    }
}
