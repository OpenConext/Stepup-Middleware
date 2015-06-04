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
use Surfnet\Stepup\Identity\Api\Id;

class GssfId implements Id
{
    /**
     * @var string
     */
    private $gssfId;

    public function __construct($gssfId)
    {
        if (!is_string($gssfId) || trim($gssfId) === '') {
            throw InvalidArgumentException::invalidType('non-empty string', 'gssfId', $gssfId);
        }

        $this->gssfId = trim($gssfId);
    }

    /**
     * @return string
     */
    public function getGssfId()
    {
        return $this->gssfId;
    }

    public function __toString()
    {
        return $this->gssfId;
    }

    public function equals(Id $other)
    {
        if (!$other instanceof GssfId) {
            return false;
        }

        return $this->gssfId === $other->gssfId;
    }
}
