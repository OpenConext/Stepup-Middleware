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
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\AuditLogEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\SecondFactorAuditLogQuery;

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
    private static $secondFactorEvents = [
        'Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent',
        'Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent',
        'Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent',
        'Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenAndVerifiedEvent',
        'Surfnet\Stepup\Identity\Event\GssfPossessionProvenAndVerifiedEvent',
        'Surfnet\Stepup\Identity\Event\PhonePossessionProvenAndVerifiedEvent',
        'Surfnet\Stepup\Identity\Event\EmailVerifiedEvent',
        'Surfnet\Stepup\Identity\Event\SecondFactorVettedEvent',
        'Surfnet\Stepup\Identity\Event\SecondFactorVettedWithoutTokenProofOfPossession',
        'Surfnet\Stepup\Identity\Event\SecondFactorMigratedEvent',
        'Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent',
        'Surfnet\Stepup\Identity\Event\VerifiedSecondFactorRevokedEvent',
        'Surfnet\Stepup\Identity\Event\VettedSecondFactorRevokedEvent',
        'Surfnet\Stepup\Identity\Event\CompliedWithUnverifiedSecondFactorRevocationEvent',
        'Surfnet\Stepup\Identity\Event\CompliedWithVerifiedSecondFactorRevocationEvent',
        'Surfnet\Stepup\Identity\Event\CompliedWithVettedSecondFactorRevocationEvent',
        'Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaEvent',
        'Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaEvent',
        'Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaForInstitutionEvent',
        'Surfnet\Stepup\Identity\Event\IdentityAccreditedAsRaaForInstitutionEvent',
        'Surfnet\Stepup\Identity\Event\AppointedAsRaaEvent',
        'Surfnet\Stepup\Identity\Event\AppointedAsRaForInstitutionEvent',
        'Surfnet\Stepup\Identity\Event\AppointedAsRaaForInstitutionEvent',
        'Surfnet\Stepup\Identity\Event\AppointedAsRaEvent',
        'Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedEvent',
        'Surfnet\Stepup\Identity\Event\RegistrationAuthorityRetractedForInstitutionEvent',
    ];

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) - The filtering switch triggers the CyclomaticComplexity, it does
     *                                                 not actually make the class complex or hard to maintain.
     * @param SecondFactorAuditLogQuery $query
     * @return Query
     */
    public function createSecondFactorSearchQuery(SecondFactorAuditLogQuery $query)
    {
        $queryBuilder = $this
            ->createQueryBuilder('al')
            ->where('al.identityInstitution = :identityInstitution')
            ->setParameter('identityInstitution', $query->identityInstitution)
            ->andWhere('al.identityId = :identityId')
            ->andWhere('al.event IN (:secondFactorEvents)')
            ->setParameter('identityId', $query->identityId)
            ->setParameter('secondFactorEvents', self::$secondFactorEvents);

        switch ($query->orderBy) {
            case 'secondFactorType':
            case 'secondFactorIdentifier':
            case 'recordedOn':
            case 'actorCommonName':
            case 'actorInstitution':
                $queryBuilder->orderBy(
                    sprintf('al.%s', $query->orderBy),
                    $query->orderDirection === 'desc' ? 'DESC' : 'ASC'
                );
                break;
            default:
                throw new RuntimeException(sprintf('Unknown order by column "%s"', $query->orderBy));
        }

        return $queryBuilder->getQuery();
    }

    /**
     * @param IdentityId $actorId
     * @return AuditLogEntry[]
     */
    public function findEntriesWhereIdentityIsActorOnly(IdentityId $actorId)
    {
        return $this->createQueryBuilder('al')
            ->where('al.actorId = :actorId')
            ->andWhere('al.identityId != :actorId')
            ->setParameter('actorId', $actorId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param IdentityId $identityId
     * @return void
     */
    public function removeByIdentityId(IdentityId $identityId)
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete($this->_entityName, 'al')
            ->where('al.identityId = :identityId')
            ->setParameter('identityId', $identityId->getIdentityId())
            ->getQuery()
            ->execute();
    }

    /**
     * @param AuditLogEntry $entry
     */
    public function save(AuditLogEntry $entry)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($entry);
        $entityManager->flush();
    }

    public function saveAll(array $entries)
    {
        $entityManager = $this->getEntityManager();

        foreach ($entries as $entry) {
            $entityManager->persist($entry);
        }

        $entityManager->flush();
    }
}
