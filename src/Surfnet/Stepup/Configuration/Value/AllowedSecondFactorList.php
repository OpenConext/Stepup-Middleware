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
use Surfnet\StepupBundle\Value\SecondFactorType;

final class AllowedSecondFactorList implements JsonSerializable, IteratorAggregate
{
    /**
     * @var SecondFactorType[]
     */
    private $allowedSecondFactors;

    private function __construct(array $allowedSecondFactors)
    {
        foreach ($allowedSecondFactors as $allowedSecondFactor) {
            $this->initializeWith($allowedSecondFactor);
        }
    }

    /**
     * @return AllowedSecondFactorList
     */
    public static function blank()
    {
        return new self([]);
    }

    /**
     * @param $allowedSecondFactors
     * @return AllowedSecondFactorList
     */
    public static function ofTypes($allowedSecondFactors)
    {
        return new self($allowedSecondFactors);
    }

    /**
     * @param SecondFactorType $secondFactor
     * @return bool
     */
    public function allows(SecondFactorType $secondFactor)
    {
        if (empty($this->allowedSecondFactors)) {
            return true;
        }

        foreach ($this->allowedSecondFactors as $allowedSecondFactor) {
            if ($allowedSecondFactor->equals($secondFactor)) {
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

    private function initializeWith(SecondFactorType $allowedSecondFactor)
    {
        $this->allowedSecondFactors[] = $allowedSecondFactor;
    }
}
