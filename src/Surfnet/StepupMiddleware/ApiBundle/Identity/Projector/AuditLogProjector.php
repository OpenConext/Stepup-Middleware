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

use Broadway\Domain\DomainMessage;
use Broadway\ReadModel\ProjectorInterface;
use DateTime as CoreDateTime;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Event\AuditableEvent;
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
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        $event = $domainMessage->getPayload();

        if (!$event instanceof AuditableEvent) {
            return;
        }

        $auditLogMetadata = $event->getAuditLogMetadata();
        $metadata = $domainMessage->getMetadata()->serialize();

        $entry = new AuditLogEntry();

        if (isset($metadata['actorId'])) {
            $entry->actorId = $metadata['actorId'];
        }

        if (isset($metadata['actorInstitution'])) {
            $entry->actorInstitution = $metadata['actorInstitution'];
        }

        $entry->identityId = (string) $auditLogMetadata->identityId;
        $entry->identityInstitution = $auditLogMetadata->identityInstitution;
        $entry->event = get_class($event);
        $entry->recordedOn = new DateTime(new CoreDateTime($domainMessage->getRecordedOn()->toString()));

        if ($auditLogMetadata->secondFactorId) {
            $entry->secondFactorId = (string) $auditLogMetadata->secondFactorId;
        }

        if ($auditLogMetadata->secondFactorType) {
            $entry->secondFactorType = (string) $auditLogMetadata->secondFactorType;
        }

        $this->auditLogRepository->save($entry);
    }
}
