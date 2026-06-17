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

use RuntimeException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Dto\EmailTemplate;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Service\EmailTemplateService;

final class DiskEmailTemplateService implements EmailTemplateService
{
    public function __construct(private readonly string $templatesDir)
    {
    }

    public function findByName(string $name, string $preferredLocale, string $fallbackLocale): ?EmailTemplate
    {
        foreach ([$preferredLocale, $fallbackLocale] as $locale) {
            $path = sprintf('%s/%s/%s.html.twig', $this->templatesDir, $locale, $name);
            if (file_exists($path)) {
                $content = file_get_contents($path);
                if ($content === false) {
                    throw new RuntimeException(
                        sprintf('Email template file exists but is not readable: %s', $path),
                    );
                }
                $template = new EmailTemplate();
                $template->name = $name;
                $template->locale = $locale;
                $template->htmlContent = $content;
                return $template;
            }
        }

        return null;
    }
}
