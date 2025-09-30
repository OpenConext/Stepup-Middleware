<?php

declare(strict_types=1);

/**
 * Copyright 2025 SURFnet B.V.
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

/*
 * The SsoRegistrationBypassOption is the "GSSP fallback" option in the Stepup-Gateway for an institution that
 * forwards the second factor authentications at LoA 1.5 to the fallback GSSP when a user does not have
 * any active tokens
 */

final readonly class SsoRegistrationBypassOption implements JsonSerializable
{
    public static function getDefault(): self
    {
        return new self(false);
    }

    public function __construct(
        private bool $ssoRegistrationBypass
    ) {
    }

    public function equals(SsoRegistrationBypassOption $other): bool
    {
        return $this->ssoRegistrationBypass === $other->isEnabled();
    }

    public function isEnabled(): bool
    {
        return $this->ssoRegistrationBypass;
    }

    public function jsonSerialize(): bool
    {
        return $this->ssoRegistrationBypass;
    }
}
