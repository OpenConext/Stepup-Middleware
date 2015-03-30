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

class CommonName
{
    /**
     * @var string
     */
    private $commonName;

    /**
     * @param string $commonName the actual name, leading and trailing spaces will be stripped
     */
    public function __construct($commonName)
    {
        if (!is_string($commonName) || trim($commonName) === '') {
            throw InvalidArgumentException::invalidType('non-empty string', 'commonName', $commonName);
        }

        $this->commonName = trim($commonName);
    }

    /**
     * @return string
     */
    public function getCommonName()
    {
        return $this->commonName;
    }

    /**
     * @param CommonName $other
     * @return bool
     */
    public function equals(CommonName $other)
    {
        return $this->commonName === $other->commonName;
    }

    public function __toString()
    {
        return $this->commonName;
    }
}
