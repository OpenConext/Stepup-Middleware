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

use JsonSerializable;

abstract class VettingType implements JsonSerializable
{
    public const TYPE_ON_PREMISE = 'on-premise';
    public const TYPE_SELF_VET = 'self-vet';

    /**
     * @var string
     */
    protected $type;


    abstract public function auditLog(): string;

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
}
