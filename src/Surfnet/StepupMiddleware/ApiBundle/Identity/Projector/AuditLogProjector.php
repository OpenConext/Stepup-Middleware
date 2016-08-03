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
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Event\AuditableEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\AuditLogEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;

class AuditLogProjector implements ProjectorInterface
{
    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository
     */
    private $auditLogRepository;

    /**
     * @var \Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository
     */
    private $identityRepository;

    public function __construct(
        AuditLogRepository $auditLogRepository,
        IdentityRepository $identityRepository
    ) {
        $this->auditLogRepository = $auditLogRepository;
        $this->identityRepository = $identityRepository;
    }

    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        $event = $domainMessage->getPayload();

        if ($event instanceof IdentityForgottenEvent) {
            // Don't insert the IdentityForgottenEvent into the audit log, as we'd remove it immediately afterwards.
            $this->applyIdentityForgottenEvent($event);
        } elseif ($event instanceof AuditableEvent) {
            $this->applyAuditableEvent($event, $domainMessage);
        }
    }

    /**
     * @param AuditableEvent $event
     * @param DomainMessage  $domainMessage
     */
    private function applyAuditableEvent(AuditableEvent $event, DomainMessage $domainMessage)
    {
        $auditLogMetadata = $event->getAuditLogMetadata();
        $metadata = $domainMessage->getMetadata()->serialize();

        $entry = new AuditLogEntry();
        $entry->id = (string) Uuid::uuid4();

        if (isset($metadata['actorId'])) {
            $actor = $this->identityRepository->find($metadata['actorId']);

            if (!$actor) {
                throw new RuntimeException(sprintf(
                    'Cannot create AuditLogEntry, given Actor Identity "%s" does not exist',
                    $metadata['actorId']
                ));
            }

            $entry->actorId         = $metadata['actorId'];
            $entry->actorCommonName = $actor->commonName;
        }

        if (isset($metadata['actorInstitution'])) {
            $entry->actorInstitution = $metadata['actorInstitution'];
        }

        $entry->identityId          = (string) $auditLogMetadata->identityId;
        $entry->identityInstitution = $auditLogMetadata->identityInstitution;
        $entry->event               = get_class($event);
        $entry->recordedOn          = new DateTime(new CoreDateTime($domainMessage->getRecordedOn()->toString()));

        if ($auditLogMetadata->secondFactorId) {
            $entry->secondFactorId = (string) $auditLogMetadata->secondFactorId;
        }

        if ($auditLogMetadata->secondFactorType) {
            $entry->secondFactorType = (string) $auditLogMetadata->secondFactorType;
        }

        if ($auditLogMetadata->secondFactorIdentifier) {
            $entry->secondFactorIdentifier = (string) $auditLogMetadata->secondFactorIdentifier;
        }

        $this->auditLogRepository->save($entry);
    }

    private function applyIdentityForgottenEvent(IdentityForgottenEvent $event)
    {
        $entriesWhereActor = $this->auditLogRepository->findEntriesWhereIdentityIsActorOnly($event->identityId);
        foreach ($entriesWhereActor as $auditLogEntry) {
            $auditLogEntry->actorCommonName = CommonName::unknown();
        }

        $this->auditLogRepository->saveAll($entriesWhereActor);
        $this->auditLogRepository->removeByIdentityId($event->identityId);
    }
}
