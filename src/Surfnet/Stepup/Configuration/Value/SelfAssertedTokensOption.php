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

namespace Surfnet\Stepup\Configuration\Value;

use JsonSerializable;

final readonly class SelfAssertedTokensOption implements JsonSerializable
{
    public static function getDefault(): self
    {
        return new self(false);
    }

    public function __construct(
        private bool $allowed
    ) {
    }

    public function equals(SelfAssertedTokensOption $other): bool
    {
        return $this->allowed === $other->allowed;
    }

    public function isEnabled(): bool
    {
        return $this->allowed;
    }

    public function jsonSerialize(): bool
    {
        return $this->allowed;
    }
}
