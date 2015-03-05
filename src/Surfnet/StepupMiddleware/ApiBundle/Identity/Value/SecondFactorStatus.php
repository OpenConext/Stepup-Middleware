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

final class SecondFactorStatus
{
    /**
     * @var string
     */
    private $status;

    public static function unverified()
    {
        return new self('unverified');
    }

    public static function verified()
    {
        return new self('verified');
    }

    public static function vetted()
    {
        return new self('vetted');
    }

    public static function revoked()
    {
        return new self('revoked');
    }

    /**
     * @param string $status
     */
    private function __construct($status)
    {
        $this->status = $status;
    }

    /**
     * @param mixed $other
     * @return bool
     */
    public function equals($other)
    {
        return $other instanceof self && $this->status === $other->status;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->status;
    }
}
