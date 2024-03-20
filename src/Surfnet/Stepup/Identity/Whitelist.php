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

namespace Surfnet\Stepup\Identity;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use Surfnet\Stepup\Exception\DomainException;
use Surfnet\Stepup\Identity\Api\Whitelist as WhitelistApi;
use Surfnet\Stepup\Identity\Collection\InstitutionCollection;
use Surfnet\Stepup\Identity\Event\InstitutionsAddedToWhitelistEvent;
use Surfnet\Stepup\Identity\Event\InstitutionsRemovedFromWhitelistEvent;
use Surfnet\Stepup\Identity\Event\WhitelistCreatedEvent;
use Surfnet\Stepup\Identity\Event\WhitelistReplacedEvent;

final class Whitelist extends EventSourcedAggregateRoot implements WhitelistApi
{
    /**
     * There can ever be only one whitelist, so using a fixed UUIDv4
     */
    public const WHITELIST_AGGREGATE_ID = '125ccee5-d650-437a-a0b0-6bf17c8188fa';

    private ?InstitutionCollection $whitelist = null;

    public function __construct()
    {
    }

    public function getAggregateRootId(): string
    {
        return self::WHITELIST_AGGREGATE_ID;
    }

    public static function create(InstitutionCollection $institutionCollection): self
    {
        $whitelist = new self();
        $whitelist->apply(new WhitelistCreatedEvent($institutionCollection));

        return $whitelist;
    }

    public function replaceAll(InstitutionCollection $institutionCollection): void
    {
        $this->apply(new WhitelistReplacedEvent($institutionCollection));
    }

    public function add(InstitutionCollection $institutionCollection): void
    {
        foreach ($institutionCollection as $institution) {
            if ($this->whitelist->contains($institution)) {
                throw new DomainException(
                    sprintf(
                        'Cannot add institution "%s" as it is already whitelisted',
                        $institution,
                    ),
                );
            }
        }

        $this->apply(new InstitutionsAddedToWhitelistEvent($institutionCollection));
    }

    public function remove(InstitutionCollection $institutionCollection): void
    {
        foreach ($institutionCollection as $institution) {
            if (!$this->whitelist->contains($institution)) {
                throw new DomainException(
                    sprintf(
                        'Cannot remove institution "%s" as it is not whitelisted',
                        $institution,
                    ),
                );
            }
        }

        $this->apply(new InstitutionsRemovedFromWhitelistEvent($institutionCollection));
    }

    protected function applyWhitelistCreatedEvent(WhitelistCreatedEvent $event): void
    {
        $this->whitelist = new InstitutionCollection();
        $this->whitelist->addAllFrom($event->whitelistedInstitutions);
    }

    protected function applyWhitelistReplacedEvent(WhitelistReplacedEvent $event): void
    {
        $this->whitelist = new InstitutionCollection();
        $this->whitelist->addAllFrom($event->whitelistedInstitutions);
    }

    protected function applyInstitutionsAddedToWhitelistEvent(InstitutionsAddedToWhitelistEvent $event): void
    {
        $this->whitelist->addAllFrom($event->addedInstitutions);
    }

    protected function applyInstitutionsRemovedFromWhitelistEvent(InstitutionsRemovedFromWhitelistEvent $event): void
    {
        $this->whitelist->removeAllIn($event->removedInstitutions);
    }
}
