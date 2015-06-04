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

class CommonName implements Id
{
    /**
     * @var string
     */
    private $commonName;

    /**
     * @return self
     */
    public static function unknown()
    {
        return new self('—');
    }

    /**
     * @param string $commonName
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

    public function __toString()
    {
        return $this->commonName;
    }

    public function equals(Id $other)
    {
        if (!$other instanceof CommonName) {
            return false;
        }

        return $this->commonName === $other->commonName;
    }
}
