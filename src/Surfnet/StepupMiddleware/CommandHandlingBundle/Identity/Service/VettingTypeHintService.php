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
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\VettingTypeHint;
use Surfnet\StepupMiddleware\ApiBundle\Exception\NotFoundException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VettingTypeHint as VettingTypeHintEntity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VettingTypeHintRepository;

class VettingTypeHintService
{
    public function __construct(
        private readonly VettingTypeHintRepository $repository,
        private readonly array $locales,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function collectionFrom(array $hints): VettingTypeHintCollection
    {
        $elements = [];
        foreach ($hints as $locale => $hint) {
            if ($this->unknownLocale($locale)) {
                $this->logger->warning(
                    sprintf(
                        'Received unsupported locale %s while processing the vetting type hints. Allowed locales are: %s.',
                        $locale,
                        implode(', ', $this->locales),
                    ),
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

    public function findBy(Institution $institution): VettingTypeHintEntity
    {
        $result = $this->repository->find((string)$institution);
        if (!$result) {
            throw new NotFoundException(sprintf('Vetting type hint not found for institution %s', $institution));
        }
        return $result;
    }
}
