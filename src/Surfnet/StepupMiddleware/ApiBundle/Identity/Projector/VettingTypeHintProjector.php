<?php

/**
 * Copyright 2022 SURFnet bv
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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Projector;

use Broadway\ReadModel\Projector;
use Surfnet\Stepup\Configuration\EventSourcing\InstitutionConfigurationRepository;
use Surfnet\Stepup\Identity\Event\VettingTypeHintsSavedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VettingTypeHint;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VettingTypeHintRepository;

/**
 * Project vetting type hints
 *
 * These are set by RA's to inform Identities on which vetting type
 * they should use during token registration.
 */
class VettingTypeHintProjector extends Projector
{
    private VettingTypeHintRepository $vettingTypeHintRepository;

    public function __construct(VettingTypeHintRepository $vettingTypeHintRepository)
    {
        $this->vettingTypeHintRepository = $vettingTypeHintRepository;
    }

    public function applyVettingTypeHintsSavedEvent(VettingTypeHintsSavedEvent $event): void
    {
        $entity = $this->vettingTypeHintRepository->find($event->institution);
        if (!$entity) {
            $entity = new VettingTypeHint();
            $entity->institution = $event->institution;
        }
        $entity->hints = $event->hints;
        $this->vettingTypeHintRepository->save($entity);
    }
}
