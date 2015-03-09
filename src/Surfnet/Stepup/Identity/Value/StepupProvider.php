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

namespace Surfnet\Stepup\Identity\Value;

use Surfnet\Stepup\Exception\InvalidArgumentException;

class StepupProvider
{
    /**
     * @var string
     */
    private $provider;

    public function __construct($provider)
    {
        if (!is_string($provider) || strlen(trim($provider)) === 0) {
            throw InvalidArgumentException::invalidType('non-empty string', 'provider', $provider);
        }

        $this->provider = trim($provider);
    }

    public function __toString()
    {
        return $this->provider;
    }

    public function equals(StepupProvider $other)
    {
        return $this->provider === $other->provider;
    }
}
