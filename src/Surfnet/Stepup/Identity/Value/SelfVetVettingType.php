<?php

/**
 * Copyright 2021 SURFnet B.V.
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

namespace Surfnet\Stepup\Identity\Value;

use Surfnet\StepupBundle\Value\Loa;

class SelfVetVettingType implements VettingType
{
    /**
     * @var string
     */
    protected $type = VettingType::TYPE_SELF_VET;

    public function __construct(private readonly Loa $authoringLoa)
    {
    }

    public static function deserialize(array $data): self
    {
        $loa = new Loa($data['loa']['level'], $data['loa']['identifier']);
        return new self($loa);
    }

    public function auditLog(): string
    {
        return sprintf(' (self vetted using LoA: %s)', (string)$this->authoringLoa());
    }

    public function authoringLoa(): Loa
    {
        return $this->authoringLoa;
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type(),
            'loa' => [
                'level' => $this->authoringLoa->getLevel(),
                'identifier' => (string)$this->authoringLoa,
            ],
        ];
    }

    public function type(): string
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return $this->type();
    }

    public function getDocumentNumber(): ?DocumentNumber
    {
        return null;
    }
}
