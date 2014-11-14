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
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\SecondFactor;

class SecondFactorRepository extends EntityRepository
{
    /**
     * @param string $identityId
     * @return SecondFactor[]
     */
    public function findByIdentity($identityId)
    {
        return $this->createQueryBuilder('sf')
            ->where('sf.identity = :identityId')
            ->setParameter('identityId', $identityId)
            ->getQuery()
            ->getResult();
    }

    public function proveYubikeyPossession(
        IdentityId $identityId,
        SecondFactorId $secondFactorId,
        YubikeyPublicId $yubikeyPublicId
    ) {
        $entityManager = $this->getEntityManager();

        /** @var Identity $identity */
        $identity = $entityManager->getReference(
            'Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity',
            (string) $identityId
        );

        $secondFactor = new SecondFactor($identity, (string) $secondFactorId, 'yubikey', (string) $yubikeyPublicId);
        $identity->addSecondFactor($secondFactor);

        $entityManager->persist($secondFactor);
        $entityManager->flush();
    }

    public function provePhonePossession(
        IdentityId $identityId,
        SecondFactorId $secondFactorId,
        PhoneNumber $phoneNumber
    ) {
        $entityManager = $this->getEntityManager();

        /** @var Identity $identity */
        $identity = $entityManager->getReference(
            'Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity',
            (string) $identityId
        );

        $secondFactor = new SecondFactor($identity, (string) $secondFactorId, 'sms', (string) $phoneNumber);
        $identity->addSecondFactor($secondFactor);

        $entityManager->persist($secondFactor);
        $entityManager->flush();
    }
}
