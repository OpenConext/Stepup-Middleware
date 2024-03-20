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

use Stringable;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\ApiBundle\Exception\RuntimeException;

class AuthorityRole implements Stringable
{
    public const ROLE_RA = 'ra';
    public const ROLE_RAA = 'raa';
    public const ROLE_SRAA = 'sraa';

    /**
     * @var string
     */
    private string $role;

    public function __construct($role)
    {
        if (!in_array($role, [self::ROLE_RA, self::ROLE_RAA, self::ROLE_SRAA])) {
            throw InvalidArgumentException::invalidType(
                'One of AuthorityRole::ROLE_RA, AuthorityRole::ROLE_RAA or AuthorityRole::ROLE_SRAA',
                'role',
                $role,
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
    public static function ra(): self
    {
        return new self(self::ROLE_RA);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName) this is the actual used notation
     *
     * @return AuthorityRole
     */
    public static function raa(): self
    {
        return new self(self::ROLE_RAA);
    }

    /**
     * @return AuthorityRole
     */
    public static function fromRegistrationAuthorityRole(RegistrationAuthorityRole $registrationAuthorityRole): AuthorityRole
    {
        if ($registrationAuthorityRole->isRa()) {
            return static::ra();
        } elseif ($registrationAuthorityRole->isRaa()) {
            return static::raa();
        }

        throw new RuntimeException(
            sprintf(
                'AuthorityRole cannot be created from RegistrationAuthorityRole of value "%s"',
                $registrationAuthorityRole,
            ),
        );
    }

    /**
     * @return bool
     */
    public function equals(AuthorityRole $other): bool
    {
        return $this->role === $other->role;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    public function __toString(): string
    {
        return $this->role;
    }
}
