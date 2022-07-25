<?php

/**
 * Copyright 2022 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service;

use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Identity\Collection\VettingTypeHintCollection;
use Surfnet\Stepup\Identity\Value\VettingTypeHint;

class VettingTypeHintService
{
    /**
     * @var array
     */
    private $locales;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(array $locales, LoggerInterface $logger)
    {
        $this->locales = $locales;
        $this->logger = $logger;
    }

    public function collectionFrom(array $hints): VettingTypeHintCollection
    {
        $elements = [];
        foreach ($hints as $locale => $hint) {
            if ($this->unknownLocale($locale)) {
                $this->logger->warning(
                    sprintf(
                        'Received unsupported locale %s while processing the vetting type hints. ' .
                        'Allowed locales are: %s.',
                        $locale,
                        implode(', ', $this->locales)
                    )
                );
                continue;
            }
            $elements[] = new VettingTypeHint($locale, $hint);
        }
        return new VettingTypeHintCollection($elements);
    }

    private function unknownLocale(string $locale): bool
    {
        return !in_array($locale, $this->locales, true);
    }
}
