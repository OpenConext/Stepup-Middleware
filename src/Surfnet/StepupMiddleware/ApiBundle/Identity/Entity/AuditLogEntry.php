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
use Surfnet\StepupMiddleware\ApiBundle\Exception\LogicException;

/**
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 *
 * @ORM\Entity(repositoryClass="Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\AuditLogRepository")
 * @ORM\Table(
 *      name="audit_log",
 *      indexes={
 *          @ORM\Index(name="idx_auditlog_actorid", columns={"actor_id"}),
 *          @ORM\Index(name="idx_auditlog_identityid", columns={"identity_id"}),
 *          @ORM\Index(name="idx_auditlog_identityinstitution", columns={"identity_institution"}),
 *          @ORM\Index(name="idx_auditlog_secondfactorid", columns={"second_factor_id"}),
 *          @ORM\Index(name="idx_auditlog_ra_institution", columns={"ra_institution"}),
 *      }
 * )
 */
class AuditLogEntry implements JsonSerializable
{
    /**
     * Maps event FQCNs to action names.
     *
     * @var string[]
     */
    private $eventActionMap = [
        'Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent' => 'revoked_by_ra',
        'Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent'   => 'revoked_by_ra',
        'Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent'     => 'revoked_by_ra',
        'Surfnet\Stepup\Identity\Event\EmailVerifiedEvent'                                => 'email_verified',
        'Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent'                         => 'possession_proven',
        'Surfnet\Stepup\Identity\Event\GssfPossessionProvenAndVerifiedEvent'              => 'possession_proven',
        'Surfnet\Stepup\Identity\Event\IdentityCreatedEvent'                              => 'created',
        'Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent'                         => 'email_changed',
        'Surfnet\Stepup\Identity\Event\IdentityRenamedEvent'                              => 'renamed',
        'Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent'                        => 'possession_proven',
        'Surfnet\Stepup\Identity\Event\PhonePossessionProvenAndVerifiedEvent'             => 'possession_proven',
        'Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent'                           => 'vetted',
        'Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession' => 'vetted_possession_unknown',
        'Surfnet\Stepup\Identity\Event\SecondFactorMigratedToEvent' => 'migrated_to',
        'Surfnet\Stepup\Identity\Event\SecondFactorMigratedEvent' => 'migrated_from',
        'Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent'                => 'revoked',
        'Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent'                  => 'revoked',
        'Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent'                    => 'revoked',
        'Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent'                      => 'possession_proven',
        'Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenAndVerifiedEvent'           => 'possession_proven',
        'Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent'              => 'bootstrapped',
        'Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaEvent'                      => 'accredited_as_raa',
        'Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent'                       => 'accredited_as_ra',
        'Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaForInstitutionEvent'         => 'accredited_as_ra',
        'Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaForInstitutionEvent'        => 'accredited_as_raa',
        'Surfnet\Stepup\Identity\Event\AppointedAsRaaEvent'                               => 'appointed_as_raa',
        'Surfnet\Stepup\Identity\Event\AppointedAsRaEvent'                                => 'appointed_as_ra',
        'Surfnet\Stepup\Identity\Event\AppointedAsRaaForInstitutionEvent'                 => 'appointed_as_raa',
        'Surfnet\Stepup\Identity\Event\AppointedAsRaForInstitutionEvent'                  => 'appointed_as_ra',
        'Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedEvent'               => 'retracted_as_ra',
        'Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedForInstitutionEvent' => 'retracted_as_ra',
    ];

    /**
     * @ORM\Id
     * @ORM\Column(length=36)
     *
     * @var string
     */
    public $id;

    /**
     * @ORM\Column(length=36, nullable=true)
     *
     * @var string|null
     */
    public $actorId;

    /**
     * @ORM\Column(type="stepup_common_name", nullable=true)
     *
     * @var \Surfnet\Stepup\Identity\Value\CommonName
     */
    public $actorCommonName;

    /**
     * @ORM\Column(type="institution", nullable=true)
     *
     * @var \Surfnet\Stepup\Identity\Value\Institution|null
     */
    public $actorInstitution;

    /**
     * Only in certain situations will this field be filled, It represents the RA institution the
     * event log entry is targeted at. For example. John Doe is accredited to become RA by Joe from
     * institution-a. The actual institution John is appointed RA for is stored in this field.
     *
     * @ORM\Column(length=255, nullable=true)
     *
     * @var string|null
     */
    public $raInstitution;

    /**
     * @ORM\Column(length=36)
     *
     * @var string
     */
    public $identityId;

    /**
     * @ORM\Column(type="institution")
     *
     * @var \Surfnet\Stepup\Identity\Value\Institution
     */
    public $identityInstitution;

    /**
     * @ORM\Column(length=36, nullable=true)
     *
     * @var string|null
     */
    public $secondFactorId;

    /**
     * @ORM\Column(length=255, nullable=true)
     *
     * @var string
     */
    public $secondFactorIdentifier;

    /**
     * @ORM\Column(length=36, nullable=true)
     *
     * @var string|null
     */
    public $secondFactorType;

    /**
     * @ORM\Column(length=255)
     *
     * @var string
     */
    public $event;

    /**
     * @ORM\Column(type="stepup_datetime")
     *
     * @var \Surfnet\Stepup\DateTime\DateTime
     */
    public $recordedOn;

    public function jsonSerialize()
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
            'action' => $this->mapEventToAction($this->event),
            'recorded_on' => (string) $this->recordedOn,
        ];
    }

    /**
     * Maps an event FQCN to an action name (eg. '...\Event\IdentityCreatedEvent' to 'created').
     *
     * @param string $event Event FQCN
     * @return string Action name
     */
    private function mapEventToAction($event)
    {
        if (!isset($this->eventActionMap[$event])) {
            throw new LogicException(sprintf("Action name for event '%s' not registered", $event));
        }

        return $this->eventActionMap[$event];
    }
}
