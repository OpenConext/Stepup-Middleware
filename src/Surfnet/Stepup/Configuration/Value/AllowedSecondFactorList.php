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
use Broadway\Serializer\Serializable as SerializableInterface;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use Surfnet\StepupBundle\Value\SecondFactorType;

/**
 * @implements IteratorAggregate<SecondFactorType>
 */
final class AllowedSecondFactorList implements JsonSerializable, IteratorAggregate, SerializableInterface
{
    /**
     * @var SecondFactorType[]
     */
    private array $allowedSecondFactors = [];

    private function __construct(array $allowedSecondFactors)
    {
        foreach ($allowedSecondFactors as $allowedSecondFactor) {
            $this->initializeWith($allowedSecondFactor);
        }
    }

    /**
     * @return AllowedSecondFactorList
     */
    public static function blank(): self
    {
        return new self([]);
    }

    public static function ofTypes(array $allowedSecondFactors): self
    {
        return new self($allowedSecondFactors);
    }

    public function allows(SecondFactorType $secondFactor): bool
    {
        return $this->isBlank() || $this->contains($secondFactor);
    }

    public function isBlank(): bool
    {
        return $this->allowedSecondFactors === [];
    }

    public function contains(SecondFactorType $secondFactor): bool
    {
        foreach ($this->allowedSecondFactors as $allowedSecondFactor) {
            if ($allowedSecondFactor->equals($secondFactor)) {
                return true;
            }
        }

        return false;
    }

    public function equals(AllowedSecondFactorList $other): bool
    {
        if (count($other->allowedSecondFactors) !== count($this->allowedSecondFactors)) {
            return false;
        }

        foreach ($other->allowedSecondFactors as $allowedSecondFactor) {
            if (!$this->contains($allowedSecondFactor)) {
                return false;
            }
        }

        return true;
    }

    public static function deserialize(array $data): self
    {
        $secondFactorTypes = array_map(
            fn($secondFactorString): SecondFactorType => new SecondFactorType($secondFactorString),
            $data['allowed_second_factors'],
        );

        return new self($secondFactorTypes);
    }

    public function serialize(): array
    {
        $allowedSecondFactors = array_map(
            fn(SecondFactorType $secondFactorType): string => $secondFactorType->getSecondFactorType(),
            $this->allowedSecondFactors,
        );

        return [
            'allowed_second_factors' => $allowedSecondFactors,
        ];
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->allowedSecondFactors);
    }

    public function jsonSerialize(): array
    {
        return $this->allowedSecondFactors;
    }

    private function initializeWith(SecondFactorType $allowedSecondFactor): void
    {
        if (!$this->contains($allowedSecondFactor)) {
            $this->allowedSecondFactors[] = $allowedSecondFactor;
        }
    }
}
