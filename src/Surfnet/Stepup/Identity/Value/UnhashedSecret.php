<?php

/**
 * Copyright 2022 SURFnet bv
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

/**
 * Unhashed secret
 *
 * This VO can be used to hash the unhashed secret
 *
 * A HashedSecret is constructed when calling the immutable hashSecret
 * method. This method will return an instance of HashedSecret.
 */
class UnhashedSecret implements HashableSecret
{
    /**
     * Cost to compute the hash (10 is the default baseline value for hash_password)
     * Increasing this value also increases the time to generate it.
     */
    private const COST = 14;

    /**
     * The default password_hash alog is Bcrypt, other options include:\
     *  PASSWORD_ARGON2I and PASSWORD_ARGON2ID. More information in the PHP man pages
     * https://www.php.net/manual/en/function.password-hash.php
     */
    private const ALGORITHM = PASSWORD_BCRYPT;

    public function hashSecret(): HashedSecret
    {
        $hashedSecret = password_hash(
            $this->secret,
            self::ALGORITHM,
            ['cost' => self::COST],
        );
        return new HashedSecret($hashedSecret);
    }

    public function __construct(private readonly string $secret)
    {
    }

    public function getSecret(): string
    {
        return $this->secret;
    }
}
