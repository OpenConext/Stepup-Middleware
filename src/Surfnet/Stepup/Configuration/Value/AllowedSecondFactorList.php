<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace Surfnet\Stepup\Configuration\Value;

use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use Surfnet\Stepup\Exception\InvalidArgumentException;

final class AllowedSecondFactorList implements JsonSerializable, IteratorAggregate
{
    /**
     * @var SecondFactor[]
     */
    private $allowedSecondFactors;

    public function __construct(array $allowedSecondFactors)
    {
        foreach ($allowedSecondFactors as $allowedSecondFactor) {
            if (!$allowedSecondFactor instanceof SecondFactor) {
                throw InvalidArgumentException::invalidType(
                    SecondFactor::class,
                    'allowedSecondFactor',
                    $allowedSecondFactor
                );
            }
        }

        $this->allowedSecondFactors = $allowedSecondFactors;
    }

    /**
     * @param SecondFactor $other
     * @return bool
     */
    public function isAllowed(SecondFactor $other)
    {
        if (empty($this->allowedSecondFactors)) {
            return true;
        }

        foreach ($this->allowedSecondFactors as $secondFactorType) {
            if ($secondFactorType->equals($other)) {
                return true;
            }
        }

        return false;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->allowedSecondFactors);
    }

    public function jsonSerialize()
    {
        return [
            'allowedSecondFactors' => $this->allowedSecondFactors
        ];
    }
}
