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

use Surfnet\Stepup\Identity\AuditLog\Metadata;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\CommandHandlingBundle\SensitiveData\RightToObtainDataInterface;

class IdentityForgottenEvent extends IdentityEvent implements RightToObtainDataInterface
{
    protected static $whitelist = [
        'identity_id',
        'institution',
    ];

    /**
     * @return Metadata
     */
    public function getAuditLogMetadata()
    {
        $metadata                      = new Metadata();
        $metadata->identityId          = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;

        return $metadata;
    }

    public static function deserialize(array $data)
    {
        return new IdentityForgottenEvent(new IdentityId($data['identity_id']), new Institution($data['institution']));
    }

    /**
     * The data ending up in the event_stream, be careful not to include sensitive data here!
     */
    public function serialize(): array
    {
        return [
            'identity_id' => $this->identityId,
            'institution' => $this->identityInstitution
        ];
    }

    public function obtainUserData(): array
    {
        $serializedUserData = $this->serialize();
        $whitelist = array_flip(self::$whitelist);
        return array_intersect_key($serializedUserData, $whitelist);
    }
}
