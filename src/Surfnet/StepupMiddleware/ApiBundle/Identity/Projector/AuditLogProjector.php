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
use Broadway\EventHandling\EventListener;
use DateTime as CoreDateTime;
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Event\AuditableEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithRecoveryCodeRevocationEvent;
use Surfnet\Stepup\Identity\Event\IdentityForgottenEvent;
use Surfnet\Stepup\Identity\Event\RecoveryTokenRevokedEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\RecoveryTokenIdentifier;
use Surfnet\Stepup\Identity\Value\RecoveryTokenIdentifierFactory;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifierFactory;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\AuditLogEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use function get_class;
use function is_null;
use function property_exists;
use function sprintf;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AuditLogProjector implements EventListener
{
    private AuditLogRepository $auditLogRepository;

    private IdentityRepository $identityRepository;

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
    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        switch (true) {
            case $event instanceof IdentityForgottenEvent:
                // Don't insert the IdentityForgottenEvent into the audit log, as we'd remove it immediately afterwards.
                $this->applyIdentityForgottenEvent($event);
                break;
            // Finally apply the auditable event, most events are auditable this so first handle the unique variants
            case $event instanceof AuditableEvent:
                $this->applyAuditableEvent($event, $domainMessage);
                break;
        }
    }

    /**
     * @param AuditableEvent $event
     * @param DomainMessage $domainMessage
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function applyAuditableEvent(AuditableEvent $event, DomainMessage $domainMessage): void
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

        $this->augmentActorCommonName($entry, $auditLogMetadata);

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

        if (!$event instanceof RecoveryTokenRevokedEvent
            && !$event instanceof CompliedWithRecoveryCodeRevocationEvent
            && $auditLogMetadata->recoveryTokenId
        ) {
            $entry->recoveryTokenIdentifier = (string) $auditLogMetadata->recoveryTokenId;
        }

        if ($auditLogMetadata->recoveryTokenType) {
            $entry->recoveryTokenType = (string) $auditLogMetadata->recoveryTokenType;
        }

        if ($auditLogMetadata->secondFactorIdentifier) {
            $entry->secondFactorIdentifier = (string) $auditLogMetadata->secondFactorIdentifier;
        }

        if ($auditLogMetadata->raInstitution) {
            $entry->raInstitution = (string) $auditLogMetadata->raInstitution;
        }

        $this->auditLogRepository->save($entry);
    }

    private function applyIdentityForgottenEvent(IdentityForgottenEvent $event): void
    {
        $entries = $this->auditLogRepository->findByIdentityId($event->identityId);
        foreach ($entries as $auditLogEntry) {
            $auditLogEntry->actorCommonName = CommonName::unknown();

            if ($auditLogEntry->recoveryTokenIdentifier) {
                $auditLogEntry->recoveryTokenIdentifier = RecoveryTokenIdentifierFactory::unknownForType(
                    new RecoveryTokenType($auditLogEntry->recoveryTokenType)
                );
            }
        }

        $entriesWhereActor = $this->auditLogRepository->findEntriesWhereIdentityIsActorOnly($event->identityId);
        foreach ($entriesWhereActor as $auditLogEntry) {
            $auditLogEntry->actorCommonName = CommonName::unknown();
        }

        $this->auditLogRepository->saveAll($entries);
        $this->auditLogRepository->saveAll($entriesWhereActor);
    }

    private function augmentActorCommonName(AuditLogEntry $entry, Metadata $auditLogMetadata): void
    {
        if (property_exists($auditLogMetadata, 'vettingType') && !is_null($auditLogMetadata->vettingType)) {
            $entry->actorCommonName .= $auditLogMetadata->vettingType->auditLog();
        }
    }
}
