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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Service;

use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Sraa;

class SraaService
{
    /**
     * @param string[] $sraaNameIds list of name_id strings from parameters.yaml
     */
    public function __construct(private readonly array $sraaNameIds)
    {
    }

    public function findByNameId(NameId $nameId): ?Sraa
    {
        if (!in_array((string) $nameId, $this->sraaNameIds, true)) {
            return null;
        }

        return new Sraa($nameId);
    }

    /**
     * @return Sraa[]
     */
    public function findAll(): array
    {
        return array_map(
            static fn(string $nameId): Sraa => new Sraa(new NameId($nameId)),
            $this->sraaNameIds,
        );
    }

    public function contains(NameId $nameId): bool
    {
        return in_array((string) $nameId, $this->sraaNameIds, true);
    }
}
