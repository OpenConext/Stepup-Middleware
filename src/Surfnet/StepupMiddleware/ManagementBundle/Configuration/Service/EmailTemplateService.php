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

namespace Surfnet\StepupMiddleware\ManagementBundle\Configuration\Service;

use Exception;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Dto\EmailTemplate;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Service\EmailTemplateService as CommandHandlingEmailTemplateService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\RuntimeException;
use Surfnet\StepupMiddleware\ManagementBundle\Configuration\Repository\EmailTemplateRepository;

final readonly class EmailTemplateService implements CommandHandlingEmailTemplateService
{
    public function __construct(
        private EmailTemplateRepository $repository,
    ) {
    }

    /**
     * @param string $name
     * @param string $preferredLocale
     * @param string $fallbackLocale
     * @return null|EmailTemplate
     */
    public function findByName(string $name, string $preferredLocale, string $fallbackLocale): ?EmailTemplate
    {
        try {
            $emailTemplateEntity = $this->repository->findOneByName($name, $preferredLocale, $fallbackLocale);
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        if (!$emailTemplateEntity instanceof \Surfnet\StepupMiddleware\ManagementBundle\Configuration\Entity\EmailTemplate) {
            return null;
        }

        $emailTemplate = new EmailTemplate();
        $emailTemplate->name = $emailTemplateEntity->getName();
        $emailTemplate->locale = $emailTemplateEntity->getLocale();
        $emailTemplate->htmlContent = $emailTemplateEntity->getHtmlContent();

        return $emailTemplate;
    }
}
