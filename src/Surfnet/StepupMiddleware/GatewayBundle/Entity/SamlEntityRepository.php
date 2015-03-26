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

namespace Surfnet\StepupMiddleware\GatewayBundle\Entity;

use Doctrine\ORM\EntityRepository;

class SamlEntityRepository extends EntityRepository
{
    /**
     * Remove all configured SamlEntities
     */
    public function removeAll()
    {
        $this
            ->getEntityManager()
            ->createQuery(
                'DELETE FROM SurfnetStepupMiddlewareGatewayBundle:SamlEntity'
            )
            ->execute();
    }

    /**
     * Replace all configured SamlEntities with the new SamlEntities.
     *
     * Will be updated later, see https://www.pivotaltracker.com/story/show/83532704
     *
     * @param array $newSamlEntities
     */
    public function replaceAll(array $newSamlEntities)
    {
        $entityManager = $this->getEntityManager();
        $counter = 0;

        $this->removeAll();
        $entityManager->flush();

        foreach ($newSamlEntities as $samlEntity) {
            $entityManager->persist($samlEntity);

            if (++$counter % 25 === 0) {
                $entityManager->flush();
            }
        }

        $entityManager->flush();
    }
}
