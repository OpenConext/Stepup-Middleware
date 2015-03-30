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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Projector;

use Broadway\Domain\DomainMessageInterface;
use Broadway\ReadModel\ProjectorInterface;
use DateTime as CoreDateTime;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Event\IdentityEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorEvent;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\AuditLogEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository;

class AuditLogProjector implements ProjectorInterface
{
    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository
     */
    private $auditLogRepository;

    public function __construct(AuditLogRepository $auditLogRepository)
    {
        $this->auditLogRepository = $auditLogRepository;
    }

    /**
     * @param DomainMessageInterface $domainMessage
     */
    public function handle(DomainMessageInterface $domainMessage)
    {
        $event = $domainMessage->getPayload();

        if (!$event instanceof IdentityEvent) {
            return;
        }

        $metadata = $domainMessage->getMetadata()->serialize();

        $entry = new AuditLogEntry();
        $entry->actorId = isset($metadata['actorId']) ? $metadata['actorId'] : null;
        $entry->actorInstitution = isset($metadata['actorInstitution']) ? $metadata['actorInstitution'] : null;
        $entry->identityId = (string) $event->identityId;
        $entry->identityInstitution = $event->identityInstitution;
        $entry->action = get_class($event);
        $entry->recordedOn = new DateTime(new CoreDateTime($domainMessage->getRecordedOn()->toString()));

        if ($event instanceof SecondFactorEvent) {
            $entry->secondFactorId = (string) $event->secondFactorId;
            $entry->secondFactorType = (string) $event->secondFactorType;
        }

        $this->auditLogRepository->save($entry);
    }
}
