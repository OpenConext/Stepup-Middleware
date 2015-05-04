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

namespace Surfnet\StepupMiddleware\ManagementBundle\Configuration\Repository;

use Doctrine\ORM\EntityRepository;
use Surfnet\StepupMiddleware\ManagementBundle\Configuration\Entity\EmailTemplate;

final class EmailTemplateRepository extends EntityRepository
{
    /**
     * @param string $name
     * @param string $preferredLocale
     * @param string $fallbackLocale
     * @return \Surfnet\StepupMiddleware\ManagementBundle\Configuration\Entity\EmailTemplate
     */
    public function findByName($name, $preferredLocale, $fallbackLocale)
    {
        return $this
            ->createQueryBuilder('tpl')
            ->where('tpl.name = :name')
            ->setParameter('name', $name)
            ->addSelect(
                'CASE WHEN tpl.locale = :preferredLocale THEN 2
                      WHEN tpl.locale = :fallbackLocale THEN 1
                      ELSE 0
                 END AS HIDDEN localePreference'
            )
            ->setParameter('preferredLocale', $preferredLocale)
            ->setParameter('fallbackLocale', $fallbackLocale)
            ->orderBy('localePreference', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Removes all email templates.
     *
     * We hydrate all templates and remove them through the entitymanager so that they get
     * removed from the IdentityMap. This to prevent issues when replaying the events, where
     * deleting them with a delete query would cause errors due to templates not being found.
     */
    public function removeAll()
    {
        $templates = $this->findAll();
        $em = $this->getEntityManager();

        foreach ($templates as $template) {
            $em->remove($template);
        }

        $em->flush();

        unset($templates);
    }

    public function save(EmailTemplate $template)
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($template);
        $entityManager->flush();
    }
}
