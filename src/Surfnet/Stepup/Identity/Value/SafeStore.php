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

/**
 * Recovery token identifier for the SafeStore token type
 */
class SafeStore implements RecoveryTokenIdentifier
{
    /** @var Secret */
    private $secret;

    public function __construct(Secret $hashedSecret)
    {
        $this->secret = $hashedSecret;
    }

    public static function unknown(): self
    {
        return new self(new ForgottenSecret());
    }

    public static function hidden()
    {
        return new self(new HiddenSecret());
    }

    public function getValue()
    {
        return $this->secret->getSecret();
    }

    public function equals($other): bool
    {
        return $other instanceof self && $other->getValue() === $this->getValue();
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }
}
