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
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\SecondFactor;

class IdentityRepository extends EntityRepository
{
    /**
     * @param string $id
     * @return Identity|null
     */
    public function find($id)
    {
        /** @var Identity|null $identity */
        $identity = parent::find($id);

        return $identity;
    }

    /**
     * @param Identity $identity
     */
    private function save(Identity $identity)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($identity);
        $entityManager->flush();
    }

    public function create(IdentityId $identityId, NameId $nameId)
    {
        $this->save(new Identity((string) $identityId, (string) $nameId));
    }
}
