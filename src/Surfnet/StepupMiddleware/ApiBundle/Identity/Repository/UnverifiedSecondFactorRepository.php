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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchUnverifiedSecondFactorCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\UnverifiedSecondFactor;

class UnverifiedSecondFactorRepository extends EntityRepository
{
    /**
     * @param string $identityId
     * @return UnverifiedSecondFactor[]
     */
    public function findByIdentity($identityId)
    {
        return $this->createQueryBuilder('sf')
            ->where('sf.identity = :identityId')
            ->setParameter('identityId', $identityId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $nonce
     * @return UnverifiedSecondFactor[]
     */
    public function findByEmailVerificationNonce($nonce)
    {
        return $this->createQueryBuilder('sf')
            ->where('sf.emailVerificationNonce = :nonce')
            ->setParameter('nonce', $nonce)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param IdentityId $identityId
     * @param SecondFactorId $secondFactorId
     * @param YubikeyPublicId $yubikeyPublicId
     * @param string $verificationNonce
     */
    public function proveYubikeyPossession(
        IdentityId $identityId,
        SecondFactorId $secondFactorId,
        YubikeyPublicId $yubikeyPublicId,
        $verificationNonce
    ) {
        $entityManager = $this->getEntityManager();

        /** @var Identity $identity */
        $identity = $entityManager->getReference(
            'Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity',
            (string) $identityId
        );

        $secondFactor = new UnverifiedSecondFactor(
            $identity,
            (string) $secondFactorId,
            'yubikey',
            (string) $yubikeyPublicId,
            $verificationNonce
        );
        $identity->addUnverifiedSecondFactor($secondFactor);

        $entityManager->persist($secondFactor);
        $entityManager->flush();
    }

    /**
     * @param IdentityId $identityId
     * @param SecondFactorId $secondFactorId
     * @param PhoneNumber $phoneNumber
     * @param string $verificationNonce
     */
    public function provePhonePossession(
        IdentityId $identityId,
        SecondFactorId $secondFactorId,
        PhoneNumber $phoneNumber,
        $verificationNonce
    ) {
        $entityManager = $this->getEntityManager();

        /** @var Identity $identity */
        $identity = $entityManager->getReference(
            'Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity',
            (string) $identityId
        );

        $secondFactor = new UnverifiedSecondFactor(
            $identity,
            (string) $secondFactorId,
            'sms',
            (string) $phoneNumber,
            $verificationNonce
        );
        $identity->addUnverifiedSecondFactor($secondFactor);

        $entityManager->persist($secondFactor);
        $entityManager->flush();
    }

    public function verifyEmail($secondFactorId)
    {
        /** @var UnverifiedSecondFactor|null $secondFactor */
        $secondFactor = $this->find($secondFactorId);

        if (!$secondFactor) {
            return;
        }

        $secondFactor->emailVerificationNonce = null;

        $this->getEntityManager()->flush();
    }

    /**
     * @param SearchUnverifiedSecondFactorCommand $command
     * @return Query
     */
    public function createSearchQuery(SearchUnverifiedSecondFactorCommand $command)
    {
        $queryBuilder = $this->createQueryBuilder('sf');

        if ($command->identityId) {
            $queryBuilder
                ->andWhere('sf.identity = :identityId')
                ->setParameter('identityId', (string) $command->identityId);
        }

        if ($command->emailVerificationNonce) {
            $queryBuilder
                ->andWhere('sf.emailVerificationNonce = :emailVerificationNonce')
                ->setParameter('emailVerificationNonce', $command->emailVerificationNonce);
        }

        return $queryBuilder->getQuery();
    }
}
