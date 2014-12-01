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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Value;

use Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\InvalidArgumentException;

class Sender
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @param string $name
     * @param string $email
     */
    public function __construct($name, $email)
    {
        if (!is_string($name)) {
            throw InvalidArgumentException::invalidType('string', 'name', $name);
        }

        if (!is_string($email)) {
            throw InvalidArgumentException::invalidType('string', 'email', $name);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                sprintf("Invalid argument type: expected e-mail address for 'email', got '%s'", $email)
            );
        }

        $this->name = $name;
        $this->email = $email;
    }

    public function equals(self $other)
    {
        return $this == $other;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function __toString()
    {
        return sprintf('%s <%s>', $this->name, $this->email);
    }
}
