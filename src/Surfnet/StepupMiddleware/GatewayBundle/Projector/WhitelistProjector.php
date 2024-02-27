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

namespace Surfnet\StepupMiddleware\GatewayBundle\Projector;

use Broadway\ReadModel\Projector;
use Surfnet\Stepup\Identity\Event\InstitutionsAddedToWhitelistEvent;
use Surfnet\Stepup\Identity\Event\InstitutionsRemovedFromWhitelistEvent;
use Surfnet\Stepup\Identity\Event\WhitelistCreatedEvent;
use Surfnet\Stepup\Identity\Event\WhitelistReplacedEvent;
use Surfnet\StepupMiddleware\GatewayBundle\Entity\WhitelistEntry;
use Surfnet\StepupMiddleware\GatewayBundle\Repository\WhitelistEntryRepository;

class WhitelistProjector extends Projector
{
    private WhitelistEntryRepository $whitelistEntryRepository;

    /**
     * @param WhitelistEntryRepository $whitelistRepository
     */
    public function __construct(WhitelistEntryRepository $whitelistRepository)
    {
        $this->whitelistEntryRepository = $whitelistRepository;
    }

    /**
     * @param WhitelistCreatedEvent $event
     */
    protected function applyWhitelistCreatedEvent(WhitelistCreatedEvent $event)
    {
        $whitelistEntries = [];
        foreach ($event->whitelistedInstitutions as $institution) {
            $whitelistEntries[] = WhitelistEntry::createFrom($institution);
        }

        $this->whitelistEntryRepository->saveEntries($whitelistEntries);
    }

    /**
     * @param WhitelistReplacedEvent $event
     */
    protected function applyWhitelistReplacedEvent(WhitelistReplacedEvent $event)
    {
        $this->whitelistEntryRepository->removeAll();

        $whitelistEntries = [];
        foreach ($event->whitelistedInstitutions as $institution) {
            $whitelistEntries[] = WhitelistEntry::createFrom($institution);
        }

        $this->whitelistEntryRepository->saveEntries($whitelistEntries);
    }

    /**
     * @param InstitutionsAddedToWhitelistEvent $event
     */
    protected function applyInstitutionsAddedToWhitelistEvent(InstitutionsAddedToWhitelistEvent $event)
    {
        $whitelistEntries = [];
        foreach ($event->addedInstitutions as $institution) {
            $whitelistEntries[] = WhitelistEntry::createFrom($institution);
        }

        $this->whitelistEntryRepository->saveEntries($whitelistEntries);
    }

    /**
     * @param InstitutionsRemovedFromWhitelistEvent $event
     */
    protected function applyInstitutionsRemovedFromWhitelistEvent(InstitutionsRemovedFromWhitelistEvent $event)
    {
        $institutions = [];
        foreach ($event->removedInstitutions as $institution) {
            $institutions[] = $institution;
        }

        $whitelistEntries = $this->whitelistEntryRepository->findEntriesByInstitutions($institutions);

        $this->whitelistEntryRepository->remove($whitelistEntries);
    }
}
