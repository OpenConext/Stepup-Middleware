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
use Broadway\Serializer\SerializableInterface;
use IteratorAggregate;
use JsonSerializable;
use Surfnet\StepupBundle\Value\SecondFactorType;

final class AllowedSecondFactorList implements JsonSerializable, IteratorAggregate, SerializableInterface
{
    /**
     * @var SecondFactorType[]
     */
    private $allowedSecondFactors = [];

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
        return $this->isBlank() || $this->contains($secondFactor);
    }

    /**
     * @return bool
     */
    public function isBlank()
    {
        return empty($this->allowedSecondFactors);
    }

    /**
     * @param SecondFactorType $secondFactor
     * @return bool
     */
    public function contains(SecondFactorType $secondFactor)
    {
        foreach ($this->allowedSecondFactors as $allowedSecondFactor) {
            if ($allowedSecondFactor->equals($secondFactor)) {
                return true;
            }
        }

        return false;
    }

    public static function deserialize(array $data)
    {
        $secondFactorTypes = array_map(
            function ($secondFactorString) {
                return new SecondFactorType($secondFactorString);
            },
            $data['allowed_second_factors']
        );

        return new self($secondFactorTypes);
    }

    public function serialize()
    {
        $allowedSecondFactors = array_map(
            function (SecondFactorType $secondFactorType) {
                return $secondFactorType->getSecondFactorType();
            },
            $this->allowedSecondFactors
        );

        return [
            'allowed_second_factors' => $allowedSecondFactors,
        ];
    }

    public function getIterator()
    {
        return new ArrayIterator($this->allowedSecondFactors);
    }

    public function jsonSerialize()
    {
        return [
            'allowed_second_factors' => $this->allowedSecondFactors
        ];
    }

    private function initializeWith(SecondFactorType $allowedSecondFactor)
    {
        if (!$this->contains($allowedSecondFactor)) {
            $this->allowedSecondFactors[] = $allowedSecondFactor;
        }
    }
}
