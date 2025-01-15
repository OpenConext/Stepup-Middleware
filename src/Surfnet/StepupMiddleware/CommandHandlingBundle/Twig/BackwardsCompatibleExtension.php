<?php

/**
 * Copyright 2024 SURFnet bv
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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Twig;

use DateTimeInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\TwigFilter;

/**
 * This class is introduced to handle BC twig changes in email templates used in the institution configuration.
 * * We need to support both versions to support two versions of the codebase to support rolling updates.
 * * An idea was to move the email templates to disk but that would cost too much time and we still should support
 * * all historical events due to the nature of event sourcing.
 */
class BackwardsCompatibleExtension extends AbstractExtension
{
    private IntlExtension $intlExtension;

    public function __construct(IntlExtension $intlExtension)
    {
        $this->intlExtension = $intlExtension;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('localizeddate', [$this, 'localizedDate'], ['needs_environment' => true]),
        ];
    }

    // localizeddate('full', 'none', locale)
    public function localizedDate(
        Environment $env,
        DateTimeInterface|string|null $date,
        ?string $dateFormat = 'medium',
        ?string $timeFormat = 'medium',
        string $locale = null
    ): string {
        return $this->intlExtension->formatDateTime($env, $date, $dateFormat, $timeFormat, locale: $locale);
    }
}
