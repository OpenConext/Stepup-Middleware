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

use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;

class AuthorityRole
{
    const ROLE_RA   = 'ra';
    const ROLE_RAA  = 'raa';
    const ROLE_SRAA = 'sraa';

    /**
     * @var string
     */
    private $role;

    public function __construct($role)
    {
        if (!in_array($role, [self::ROLE_RA, self::ROLE_RAA, self::ROLE_SRAA])) {
            throw InvalidArgumentException::invalidType(
                'One of AuthorityRole::ROLE_RA, AuthorityRole::ROLE_RAA or AuthorityRole::ROLE_SRAA',
                'role',
                $role
            );
        }

        $this->role = $role;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName) this is the actual used notation
     * @SuppressWarnings(PHPMD.ShortMethodName)     this is the actual term
     *
     * @return AuthorityRole
     */
    public static function RA()
    {
        return new self(self::ROLE_RA);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName) this is the actual used notation
     *
     * @return AuthorityRole
     */
    public static function RAA()
    {
        return new self(self::ROLE_RAA);
    }

    /**
     * @param AuthorityRole $other
     * @return bool
     */
    public function equals(AuthorityRole $other)
    {
        return $this->role === $other->role;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    public function __toString()
    {
        return $this->role;
    }
}
