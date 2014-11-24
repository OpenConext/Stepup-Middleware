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

namespace Surfnet\Stepup\Identity\Api;

use Broadway\Domain\AggregateRoot;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;

interface Identity extends AggregateRoot
{
    /**
     * @param IdentityId  $id
     * @param Institution $institution
     * @param NameId      $nameId
     * @param string      $email
     * @param string      $commonName
     * @return Identity
     */
    public static function create(
        IdentityId $id,
        Institution $institution,
        NameId $nameId,
        $email,
        $commonName
    );

    /**
     * Construct a new aggregate root. Aggregate roots can only be affected by events, so no parameters are allowed.
     */
    public function __construct();

    /**
     * @return string
     */
    public function getCommonName();

    /**
     * @return string
     */
    public function getEmail();
}
