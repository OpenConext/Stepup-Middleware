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

use Exception;
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
    /**
     * @var Locale[]
     */
    private readonly array $locales;

    /**
     * @param Locale[] $locales
     */
    private function __construct(
        private readonly TimeFrame $emailVerificationTimeFrame,
        array $locales,
    ) {
        foreach ($locales as $index => $locale) {
            if (!$locale instanceof Locale) {
                throw InvalidArgumentException::invalidType(
                    Locale::class,
                    sprintf('locales[%s]', $index),
                    $locale,
                );
            }
        }
        $this->locales = $locales;
    }

    /**
     * @param int $emailVerificationTimeFrame positive integer
     * @param string[] $locales
     * @return ConfigurableSettings
     * @throws Exception
     * @throws Exception
     */
    public static function create(int $emailVerificationTimeFrame, array $locales): self
    {
        return new self(
            TimeFrame::ofSeconds($emailVerificationTimeFrame),
            array_map(
                fn($locale): Locale => new Locale($locale),
                $locales,
            ),
        );
    }

    /**
     * @return EmailVerificationWindow
     */
    public function createNewEmailVerificationWindow(): EmailVerificationWindow
    {
        return EmailVerificationWindow::createFromTimeFrameStartingAt(
            $this->emailVerificationTimeFrame,
            DateTime::now(),
        );
    }

    public function isSupportedLocale(Locale $locale): bool
    {
        return array_reduce(
            $this->locales,
            fn($supported, Locale $supportedLocale): bool => $supported || $supportedLocale->equals($locale),
            false,
        );
    }
}
