<?php

/**
 * Copyright 2022 SURFnet bv
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

namespace Surfnet\Stepup\Identity\Collection;

use Broadway\Serializer\Serializable as SerializableInterface;
use JsonSerializable;
use Stringable;
use Surfnet\Stepup\Exception\RuntimeException;
use Surfnet\Stepup\Identity\Value\VettingTypeHint;

final class VettingTypeHintCollection implements JsonSerializable, SerializableInterface, Stringable
{
    private array $elements = [];

    public function __construct(array $hints = [])
    {
        foreach ($hints as $hint) {
            $this->add($hint);
        }
    }

    public function add(VettingTypeHint $hint): void
    {
        if (in_array($hint, $this->elements)) {
            throw new RuntimeException(
                sprintf(
                    'Vetting type hint "%s" is already in this collection',
                    $hint,
                ),
            );
        }

        $this->elements[] = $hint;
    }

    public function jsonSerialize(): array
    {
        return $this->elements;
    }

    public static function deserialize(array $data): self
    {
        $institutions = array_map(
            fn(array $hint): VettingTypeHint => new VettingTypeHint($hint['locale'], $hint['hint']),
            $data,
        );

        return new self($institutions);
    }

    public function __toString(): string
    {
        return (string)json_encode($this->jsonSerialize());
    }

    public function serialize(): array
    {
        return array_map(fn(VettingTypeHint $hint): array => $hint->jsonSerialize(), $this->elements);
    }
}
