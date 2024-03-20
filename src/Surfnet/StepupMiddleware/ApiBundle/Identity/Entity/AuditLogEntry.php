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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Event\AppointedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaEvent;
use Surfnet\Stepup\Identity\Event\AppointedAsRaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithRecoveryCodeRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent;
use Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\PhoneRecoveryTokenPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\RecoveryTokenRevokedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedEvent;
use Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedForInstitutionEvent;
use Surfnet\Stepup\Identity\Event\SafeStoreSecretRecoveryTokenPossessionPromisedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorMigratedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorMigratedToEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent;
use Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenAndVerifiedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\Value\CommonName;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Exception\LogicException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository;

/**
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(name: 'idx_auditlog_actorid', columns: ['actor_id'])]
#[ORM\Index(name: 'idx_auditlog_identityid', columns: ['identity_id'])]
#[ORM\Index(name: 'idx_auditlog_identityinstitution', columns: ['identity_institution'])]
#[ORM\Index(name: 'idx_auditlog_secondfactorid', columns: ['second_factor_id'])]
#[ORM\Index(name: 'idx_auditlog_ra_institution', columns: ['ra_institution'])]
#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
class AuditLogEntry implements JsonSerializable
{
    /**
     * Maps event FQCNs to action names.
     *
     * @var string[]
     */
    private array $eventActionMap = [
        CompliedWithUnverifiedSecondFactorRevocationEvent::class => 'revoked_by_ra',
        CompliedWithVerifiedSecondFactorRevocationEvent::class => 'revoked_by_ra',
        CompliedWithVettedSecondFactorRevocationEvent::class => 'revoked_by_ra',
        EmailVerifiedEvent::class => 'email_verified',
        GssfPossessionProvenEvent::class => 'possession_proven',
        GssfPossessionProvenAndVerifiedEvent::class => 'possession_proven',
        IdentityCreatedEvent::class => 'created',
        IdentityEmailChangedEvent::class => 'email_changed',
        IdentityRenamedEvent::class => 'renamed',
        PhonePossessionProvenEvent::class => 'possession_proven',
        PhonePossessionProvenAndVerifiedEvent::class => 'possession_proven',
        SecondFactorVettedEvent::class => 'vetted',
        SecondFactorVettedWithoutTokenProofOfPossession::class => 'vetted_possession_unknown',
        SecondFactorMigratedToEvent::class => 'migrated_to',
        SecondFactorMigratedEvent::class => 'migrated_from',
        UnverifiedSecondFactorRevokedEvent::class => 'revoked',
        VerifiedSecondFactorRevokedEvent::class => 'revoked',
        VettedSecondFactorRevokedEvent::class => 'revoked',
        YubikeyPossessionProvenEvent::class => 'possession_proven',
        YubikeyPossessionProvenAndVerifiedEvent::class => 'possession_proven',
        YubikeySecondFactorBootstrappedEvent::class => 'bootstrapped',
        IdentityAccreditedAsRaaEvent::class => 'accredited_as_raa',
        IdentityAccreditedAsRaEvent::class => 'accredited_as_ra',
        IdentityAccreditedAsRaForInstitutionEvent::class => 'accredited_as_ra',
        IdentityAccreditedAsRaaForInstitutionEvent::class => 'accredited_as_raa',
        AppointedAsRaaEvent::class => 'appointed_as_raa',
        AppointedAsRaEvent::class => 'appointed_as_ra',
        AppointedAsRaaForInstitutionEvent::class => 'appointed_as_raa',
        AppointedAsRaForInstitutionEvent::class => 'appointed_as_ra',
        RegistrationAuthorityRetractedEvent::class => 'retracted_as_ra',
        RegistrationAuthorityRetractedForInstitutionEvent::class => 'retracted_as_ra',
        SafeStoreSecretRecoveryTokenPossessionPromisedEvent::class => 'recovery_token_possession_promised',
        RecoveryTokenRevokedEvent::class => 'recovery_token_revoked',
        PhoneRecoveryTokenPossessionProvenEvent::class => 'recovery_token_possession_proven',
        CompliedWithRecoveryCodeRevocationEvent::class => 'recovery_token_revoked',
    ];

    #[ORM\Id]
    #[ORM\Column(length: 36)]
    public string $id;

    #[ORM\Column(length: 36, nullable: true)]
    public ?string $actorId;

    #[ORM\Column(type: 'stepup_common_name', nullable: true)]
    public CommonName $actorCommonName;

    #[ORM\Column(type: 'institution', nullable: true)]
    public ?Institution $actorInstitution;

    /**
     * Only in certain situations will this field be filled, It represents the RA institution the
     * event log entry is targeted at. For example. John Doe is accredited to become RA by Joe from
     * institution-a. The actual institution John is appointed RA for is stored in this field.
     */
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $raInstitution;

    #[ORM\Column(length: 36)]
    public string $identityId;

    #[ORM\Column(type: 'institution')]
    public Institution $identityInstitution;

    #[ORM\Column(length: 36, nullable: true)]
    public ?string $secondFactorId;

    #[ORM\Column(length: 255, nullable: true)]
    public string $secondFactorIdentifier;

    #[ORM\Column(length: 36, nullable: true)]
    public ?string $secondFactorType;

    #[ORM\Column(length: 255, nullable: true)]
    public string $recoveryTokenIdentifier;

    #[ORM\Column(length: 36, nullable: true)]
    public ?string $recoveryTokenType;

    #[ORM\Column(length: 255)]
    public string $event;

    #[ORM\Column(type: 'stepup_datetime')]
    public DateTime $recordedOn;

    public function jsonSerialize(): array
    {
        return [
            'actor_id' => $this->actorId,
            'actor_institution' => $this->actorInstitution ? (string)$this->actorInstitution : null,
            'actor_common_name' => $this->actorCommonName,
            'identity_id' => $this->identityId,
            'identity_institution' => (string)$this->identityInstitution,
            'ra_institution' => (string)$this->raInstitution,
            'second_factor_id' => $this->secondFactorId,
            'second_factor_type' => $this->secondFactorType ? (string)$this->secondFactorType : null,
            'second_factor_identifier' => $this->secondFactorIdentifier,
            'recovery_token_type' => $this->recoveryTokenType,
            'recovery_token_identifier' => $this->recoveryTokenIdentifier,
            'action' => $this->mapEventToAction($this->event),
            'recorded_on' => (string)$this->recordedOn,
        ];
    }

    /**
     * Maps an event FQCN to an action name (eg. '...\Event\IdentityCreatedEvent' to 'created').
     *
     * @param string $event Event FQCN
     * @return string Action name
     */
    private function mapEventToAction(string $event): string
    {
        if (!isset($this->eventActionMap[$event])) {
            throw new LogicException(sprintf("Action name for event '%s' not registered", $event));
        }

        return $this->eventActionMap[$event];
    }
}
