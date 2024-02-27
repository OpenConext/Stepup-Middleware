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
     * Replace all configured service provider SamlEntities with the new SamlEntities.
     */
    public function replaceAllSps(array $newSamlEntities): void
    {
        $this->replaceAllOfType(SamlEntity::TYPE_SP, $newSamlEntities);
    }

    /**
     * Replace all configured identity provider SamlEntities with the new SamlEntities.
     */
    public function replaceAllIdps(array $newSamlEntities): void
    {
        $this->replaceAllOfType(SamlEntity::TYPE_IDP, $newSamlEntities);
    }

    /**
     * Replace all configured SamlEntities with the new SamlEntities.
     *
     * Will be updated later, see https://www.pivotaltracker.com/story/show/83532704
     */
    private function replaceAllOfType(string $type, array $newSamlEntities): void
    {
        $entityManager = $this->getEntityManager();
        $counter = 0;

        $this->removeAllOfType($type);
        $entityManager->flush();

        foreach ($newSamlEntities as $samlEntity) {
            $entityManager->persist($samlEntity);

            if (++$counter % 25 === 0) {
                $entityManager->flush();
            }
        }

        $entityManager->flush();
    }

    /**
     * Remove all configured SamlEntities of a specific type
     *
     * @param string $type
     */
    private function removeAllOfType($type): void
    {
        $this
            ->getEntityManager()
            ->createQuery(
                'DELETE FROM SurfnetStepupMiddlewareGatewayBundle:SamlEntity se WHERE se.type = :type',
            )
            ->execute(['type' => $type]);
    }
}
