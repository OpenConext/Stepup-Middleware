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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
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
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\AuditLogEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\SecondFactorAuditLogQuery;

/**
 * @extends ServiceEntityRepository<AuditLogEntry>
 */
class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLogEntry::class);
    }

    /**
     * An array of event FQCNs that pertain to second factors (verification, vetting, revocation etc.).
     *
     * @var string[]
     */
    private static array $secondFactorEvents = [
        YubikeySecondFactorBootstrappedEvent::class,
        GssfPossessionProvenEvent::class,
        PhonePossessionProvenEvent::class,
        YubikeyPossessionProvenAndVerifiedEvent::class,
        GssfPossessionProvenAndVerifiedEvent::class,
        PhonePossessionProvenAndVerifiedEvent::class,
        EmailVerifiedEvent::class,
        SecondFactorVettedEvent::class,
        SecondFactorVettedWithoutTokenProofOfPossession::class,
        SecondFactorMigratedEvent::class,
        SecondFactorMigratedToEvent::class,
        UnverifiedSecondFactorRevokedEvent::class,
        VerifiedSecondFactorRevokedEvent::class,
        VettedSecondFactorRevokedEvent::class,
        CompliedWithUnverifiedSecondFactorRevocationEvent::class,
        CompliedWithVerifiedSecondFactorRevocationEvent::class,
        CompliedWithVettedSecondFactorRevocationEvent::class,
        IdentityAccreditedAsRaaEvent::class,
        IdentityAccreditedAsRaEvent::class,
        IdentityAccreditedAsRaForInstitutionEvent::class,
        IdentityAccreditedAsRaaForInstitutionEvent::class,
        AppointedAsRaaEvent::class,
        AppointedAsRaForInstitutionEvent::class,
        AppointedAsRaaForInstitutionEvent::class,
        AppointedAsRaEvent::class,
        RegistrationAuthorityRetractedEvent::class,
        RegistrationAuthorityRetractedForInstitutionEvent::class,
        SafeStoreSecretRecoveryTokenPossessionPromisedEvent::class,
        RecoveryTokenRevokedEvent::class,
        PhoneRecoveryTokenPossessionProvenEvent::class,
        CompliedWithRecoveryCodeRevocationEvent::class,
    ];

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) - The filtering switch triggers the CyclomaticComplexity, it does
     *                                                 not actually make the class complex or hard to maintain.
     */
    public function createSecondFactorSearchQuery(SecondFactorAuditLogQuery $query): Query
    {
        $queryBuilder = $this
            ->createQueryBuilder('al')
            ->where('al.identityInstitution = :identityInstitution')
            ->setParameter('identityInstitution', $query->identityInstitution)
            ->andWhere('al.identityId = :identityId')
            ->andWhere('al.event IN (:secondFactorEvents)')
            ->setParameter('identityId', $query->identityId)
            ->setParameter('secondFactorEvents', self::$secondFactorEvents);

        match ($query->orderBy) {
            'secondFactorType', 'secondFactorIdentifier', 'recoveryTokenType', 'recoveryTokenIdentifier', 'recordedOn', 'actorCommonName', 'actorInstitution' => $queryBuilder->orderBy(
                sprintf('al.%s', $query->orderBy),
                $query->orderDirection === 'desc' ? 'DESC' : 'ASC',
            ),
            default => throw new RuntimeException(sprintf('Unknown order by column "%s"', $query->orderBy)),
        };

        return $queryBuilder->getQuery();
    }

    /**
     * @return AuditLogEntry[]
     */
    public function findEntriesWhereIdentityIsActorOnly(IdentityId $actorId): array
    {
        return $this->createQueryBuilder('al')
            ->where('al.actorId = :actorId')
            ->andWhere('al.identityId != :actorId')
            ->setParameter('actorId', $actorId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AuditLogEntry[]
     */
    public function findByIdentityId(IdentityId $identityId): array
    {
        return $this->createQueryBuilder('al')
            ->where('al.identityId = :identityId')
            ->setParameter('identityId', $identityId)
            ->getQuery()
            ->getResult();
    }

    public function save(AuditLogEntry $entry): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($entry);
        $entityManager->flush();
    }

    public function saveAll(array $entries): void
    {
        $entityManager = $this->getEntityManager();

        foreach ($entries as $entry) {
            $entityManager->persist($entry);
        }

        $entityManager->flush();
    }
}
