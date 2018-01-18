<?php

/**
 * Copyright 2018 SURFnet B.V.
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
use Surfnet\Stepup\Exception\InvalidArgumentException;

final class VerifyEmailOption implements JsonSerializable
{
    /**
     * @var bool
     */
    private $verifyEmailOption;

    public static function getDefault()
    {
        return new self(true);
    }

    public function __construct($verifyEmailOption)
    {
        if (!is_bool($verifyEmailOption)) {
            throw InvalidArgumentException::invalidType(
                'boolean',
                'verifyEmailOption',
                $verifyEmailOption
            );
        }

        $this->verifyEmailOption = $verifyEmailOption;
    }

    /**
     * @param VerifyEmailOption $other
     * @return bool
     */
    public function equals(VerifyEmailOption $other)
    {
        return $this->verifyEmailOption === $other->verifyEmailOption;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->verifyEmailOption;
    }

    public function jsonSerialize()
    {
        return $this->verifyEmailOption;
    }
}
