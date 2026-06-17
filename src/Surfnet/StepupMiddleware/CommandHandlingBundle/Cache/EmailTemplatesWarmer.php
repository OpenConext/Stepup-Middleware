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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Cache;

use RuntimeException;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Throwable;
use Twig\Environment;

final class EmailTemplatesWarmer implements CacheWarmerInterface
{
    // Must stay in sync with the .html.twig files under config/openconext/email_templates/<locale>/
    private const TEMPLATE_NAMES = [
        'confirm_email',
        'registration_code_with_ras',
        'registration_code_with_ra_locations',
        'vetted',
        'second_factor_revoked',
        'second_factor_verification_reminder_with_ras',
        'second_factor_verification_reminder_with_ra_locations',
        'recovery_token_created',
        'recovery_token_revoked',
    ];

    /**
     * @param string[] $locales
     */
    public function __construct(
        private readonly Environment $twig,
        private readonly string $templatesDir,
        private readonly array $locales,
    ) {
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        foreach ($this->locales as $locale) {
            foreach (self::TEMPLATE_NAMES as $name) {
                $path = sprintf('%s/%s/%s.html.twig', $this->templatesDir, $locale, $name);

                if (!file_exists($path)) {
                    throw new RuntimeException(
                        sprintf('Email template file missing: %s', $path),
                    );
                }

                $content = file_get_contents($path);
                if ($content === false) {
                    throw new RuntimeException(
                        sprintf('Email template file exists but is not readable: %s', $path),
                    );
                }

                try {
                    $this->twig->createTemplate($content);
                } catch (Throwable $e) {
                    throw new RuntimeException(
                        sprintf('Email template "%s" (locale "%s") failed to compile: %s', $name, $locale, $e->getMessage()),
                        0,
                        $e,
                    );
                }
            }
        }

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }
}
