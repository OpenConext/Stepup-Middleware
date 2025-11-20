<?php

/**
 * Copyright 2025 SURFnet B.V.
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

namespace Surfnet\Stepup\Projector;

use Broadway\EventHandling\EventListener;
use Broadway\ReadModel\Projector as BroadwayProjector;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;

/**
 * @SuppressWarnings("PHPMD.NumberOfChildren") we simply have a lot of projectors
 */
abstract class Projector extends BroadwayProjector implements EventListener
{
    abstract protected function applyIdentityForgottenEvent(IdentityForgottenEvent $event): void;
}
