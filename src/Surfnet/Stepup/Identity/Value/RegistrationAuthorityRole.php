<?php

declare(strict_types=1);

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

use Broadway\Serializer\Serializable as SerializableInterface;
use Stringable;
use Surfnet\Stepup\Exception\InvalidArgumentException;

final readonly class RegistrationAuthorityRole implements SerializableInterface, Stringable
{
    public const ROLE_RA = 1;
    public const ROLE_RAA = 2;
    public const ROLE_SRAA = 3;

    public function __construct(private int $role)
    {
        if (!in_array($role, [self::ROLE_RA, self::ROLE_RAA, self::ROLE_SRAA])) {
            throw new InvalidArgumentException(
                'Invalid role given, role must be one of RegistrationAuthorityRole::[ROLE_RA|ROLE_RAA|ROLE_SRAA]',
            );
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ShortMethodName) no use in lengthening a domain term for the sake of shutting up PHPMD
     */
    public static function ra(): self
    {
        return new self(self::ROLE_RA);
    }

    public static function raa(): self
    {
        return new self(self::ROLE_RAA);
    }

    public function equals(RegistrationAuthorityRole $role): bool
    {
        return $this->role === $role->role;
    }

    /**
     * @return bool
     */
    public function isRa(): bool
    {
        return $this->role === self::ROLE_RA;
    }

    /**
     * @return bool
     */
    public function isRaa(): bool
    {
        return $this->role === self::ROLE_RAA;
    }

    public function jsonSerialize(): int
    {
        return $this->role;
    }

    public function __toString(): string
    {
        return (string)$this->role;
    }

    public static function deserialize(array $data): self
    {
        return new self($data['role']);
    }

    public function serialize(): array
    {
        return ['role' => $this->role];
    }
}
