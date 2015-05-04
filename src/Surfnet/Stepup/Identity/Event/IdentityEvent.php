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

namespace Surfnet\Stepup\Identity\Event;

use Broadway\Serializer\SerializableInterface;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;

abstract class IdentityEvent implements AuditableEvent, SerializableInterface
{
    /**
     * @var IdentityId
     */
    public $identityId;

    /**
     * @var Institution
     */
    public $identityInstitution;

    public function __construct(IdentityId $identityId, Institution $identityInstitution)
    {
        $this->identityId = $identityId;
        $this->identityInstitution = $identityInstitution;
    }
}
