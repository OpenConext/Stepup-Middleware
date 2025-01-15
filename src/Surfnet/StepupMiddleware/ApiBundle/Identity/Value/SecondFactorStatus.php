<?php

/**
 * Copyright 2014 SURFnet bv
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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Value;

use Stringable;

final readonly class SecondFactorStatus implements Stringable
{
    public static function unverified(): self
    {
        return new self('unverified');
    }

    public static function verified(): self
    {
        return new self('verified');
    }

    public static function vetted(): self
    {
        return new self('vetted');
    }

    public static function revoked(): self
    {
        return new self('revoked');
    }

    public static function forgotten(): self
    {
        return new self('forgotten');
    }

    /**
     * @return bool
     */
    public static function isValidStatus(string $status): bool
    {
        return in_array($status, ['unverified', 'verified', 'vetted', 'revoked', 'forgotten', true]);
    }

    private function __construct(private string $status)
    {
    }

    /**
     * @return bool
     */
    public function equals(SecondFactorStatus $other): bool
    {
        return $this->status === $other->status;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->status;
    }
}
