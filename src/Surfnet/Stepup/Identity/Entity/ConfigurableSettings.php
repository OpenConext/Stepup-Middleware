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

namespace Surfnet\Stepup\Identity\Entity;

use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Exception\InvalidArgumentException;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\Locale;
use Surfnet\Stepup\Identity\Value\TimeFrame;

/**
 * Entity that contains the User Defined Settings that are relevant to the domain
 */
final class ConfigurableSettings
{
    private TimeFrame $emailVerificationTimeFrame;

    /**
     * @var Locale[]
     */
    private array $locales;

    /**
     * @param TimeFrame $timeFrame
     * @param Locale[]  $locales
     */
    private function __construct(TimeFrame $timeFrame, array $locales)
    {
        foreach ($locales as $index => $locale) {
            if (!$locale instanceof Locale) {
                throw InvalidArgumentException::invalidType(
                    'Surfnet\Stepup\Identity\Value\Locale',
                    sprintf('locales[%s]', $index),
                    $locale
                );
            }
        }

        $this->emailVerificationTimeFrame = $timeFrame;
        $this->locales = $locales;
    }

    /**
     * @param int $emailVerificationTimeFrame positive integer
     * @param string[] $locales
     * @return ConfigurableSettings
     */
    public static function create($emailVerificationTimeFrame, array $locales): self
    {
        return new self(
            TimeFrame::ofSeconds($emailVerificationTimeFrame),
            array_map(
                function ($locale): Locale {
                    return new Locale($locale);
                },
                $locales
            )
        );
    }

    /**
     * @return EmailVerificationWindow
     */
    public function createNewEmailVerificationWindow()
    {
        return EmailVerificationWindow::createFromTimeFrameStartingAt(
            $this->emailVerificationTimeFrame,
            DateTime::now()
        );
    }

    /**
     * @param Locale $locale
     * @return bool
     */
    public function isSupportedLocale(Locale $locale): bool
    {
        return array_reduce(
            $this->locales,
            function ($supported, Locale $supportedLocale) use ($locale): bool {
                return $supported || $supportedLocale->equals($locale);
            },
            false
        );
    }
}
