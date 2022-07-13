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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Value;

final class RecoveryTokenStatus
{
    /**
     * @var string
     */
    private $status;

    public static function active(): self
    {
        return new self('active');
    }

    public static function revoked(): self
    {
        return new self('revoked');
    }

    public static function forgotten(): self
    {
        return new self('forgotten');
    }

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, ['active', 'revoked', 'forgotten']);
    }

    private function __construct(string $status)
    {
        $this->status = $status;
    }

    public function equals(RecoveryTokenStatus $other): bool
    {
        return $this->status === $other->status;
    }

    public function __toString(): string
    {
        return $this->status;
    }
}
