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

class UnknownVettingType implements VettingType
{
    /**
     * @var string
     */
    protected string $type = VettingType::TYPE_UNKNOWN;

    public function __construct()
    {
    }

    public function auditLog(): string
    {
        return '';
    }

    public function jsonSerialize(): array
    {
        return ['type' => $this->type()];
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
