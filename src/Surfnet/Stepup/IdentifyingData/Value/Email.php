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

namespace Surfnet\Stepup\IdentifyingData\Value;

use Surfnet\Stepup\Exception\InvalidArgumentException;

final class Email
{
    /**
     * @var string
     */
    private $email;

    /**
     * @param string $email the actual eamil, leading and trailing spaces will be stripped, no val
     */
    public function __construct($email)
    {
        if (!is_string($email) || trim($email) === '') {
            throw InvalidArgumentException::invalidType('non-empty string', 'email', $email);
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException(sprintf(
                'Email given: "%s is not a RFC 822 (https://www.ietf.org/rfc/rfc0822.txt) compliant email address"',
                $email
            ));
        }

        $this->email = trim($email);
    }

    /**
     * @return string
     */
    public function getemail()
    {
        return $this->email;
    }

    /**
     * @param Email $other
     * @return bool
     */
    public function equals(Email $other)
    {
        return $this->email === $other->email;
    }

    public function __toString()
    {
        return $this->email;
    }
}
