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

namespace Surfnet\Stepup\Identity\Value;

use Surfnet\Stepup\Exception\InvalidArgumentException;

final class RecoveryTokenType
{
    const TYPE_SMS = 'sms';
    const TYPE_SAFE_STORE = 'safe-store';

    private string $type;

    public function __construct($type)
    {
        if (!is_string($type)) {
            throw new InvalidArgumentException(sprintf('The RecoveryTokenType must be of type string, %s given', gettype($type)));
        }

        if (!in_array($type, [self::TYPE_SMS, self::TYPE_SAFE_STORE])) {
            throw new InvalidArgumentException('The RecoveryTokenType must be one of "sms" or "safe-store".');
        }

        $this->type = $type;
    }

    public static function sms(): RecoveryTokenType
    {
        return new RecoveryTokenType(self::TYPE_SMS);
    }

    public static function safeStore(): RecoveryTokenType
    {
        return new RecoveryTokenType(self::TYPE_SAFE_STORE);
    }

    public function isSms(): bool
    {
        return $this->type === self::TYPE_SMS;
    }

    public function isSafeStore(): bool
    {
        return $this->type === self::TYPE_SAFE_STORE;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return $this->type;
    }

    public function equals(RecoveryTokenType $other): bool
    {
        return $this->type === $other->getType();
    }
}
