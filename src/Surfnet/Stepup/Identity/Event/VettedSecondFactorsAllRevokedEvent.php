<?php

/**
 * Copyright 2019 SURFnet bv
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

use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;

class VettedSecondFactorsAllRevokedEvent extends IdentityEvent
{

    final public function __construct(
        IdentityId $identityId,
        Institution $identityInstitution
    ) {
        parent::__construct($identityId, $identityInstitution);
    }

    public function getAuditLogMetadata()
    {
        $metadata                         = new Metadata();
        $metadata->identityId             = $this->identityId;
        $metadata->identityInstitution    = $this->identityInstitution;

        return $metadata;
    }

    final public static function deserialize(array $data)
    {
        return new static(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution'])
        );
    }

    /**
     * The data ending up in the event_stream, be careful not to include sensitive data here!
     */
    final public function serialize(): array
    {
        return [
            'identity_id'              => (string) $this->identityId,
            'identity_institution'     => (string) $this->identityInstitution,
        ];
    }
}
