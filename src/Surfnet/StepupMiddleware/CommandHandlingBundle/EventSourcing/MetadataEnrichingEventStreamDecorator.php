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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\EventSourcing;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata as BroadwayMetadata;
use Broadway\EventSourcing\EventStreamDecorator;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Metadata;

final class MetadataEnrichingEventStreamDecorator implements EventStreamDecorator, MetadataEnricher
{
    private ?Metadata $metadata = null;

    public function setMetadata(Metadata $metadata = null): void
    {
        $this->metadata = $metadata;
    }

    public function decorateForWrite(
        $aggregateType,
        $aggregateIdentifier,
        DomainEventStream $eventStream
    ): DomainEventStream {
        if (!$this->metadata) {
            return $eventStream;
        }

        $domainMessages = [];

        foreach ($eventStream as $domainMessage) {
            /** @var DomainMessage $domainMessage */
            $domainMessages[] = $domainMessage->andMetadata(
                new BroadwayMetadata([
                    'actorId'          => $this->metadata->actorId,
                    'actorInstitution' => $this->metadata->actorInstitution
                ])
            );
        }

        return new DomainEventStream($domainMessages);
    }
}
