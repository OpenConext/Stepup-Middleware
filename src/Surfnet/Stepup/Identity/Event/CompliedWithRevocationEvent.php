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
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupBundle\Value\SecondFactorType;

abstract class CompliedWithRevocationEvent extends IdentityEvent
{
    /**
     * @var \Surfnet\Stepup\Identity\Value\IdentityId
     */
    public $authorityId;

    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorId
     */
    public $secondFactorId;

    /**
     * @var \Surfnet\StepupBundle\Value\SecondFactorType
     */
    public $secondFactorType;

    final public function __construct(
        IdentityId $identityId,
        Institution $identityInstitution,
        SecondFactorId $secondFactorId,
        SecondFactorType $secondFactorType,
        IdentityId $authorityId
    ) {
        parent::__construct($identityId, $identityInstitution);

        $this->authorityId = $authorityId;
        $this->secondFactorId = $secondFactorId;
        $this->secondFactorType = $secondFactorType;
    }

    public function getAuditLogMetadata()
    {
        $metadata = new Metadata();
        $metadata->identityId = $this->identityId;
        $metadata->identityInstitution = $this->identityInstitution;
        $metadata->secondFactorId = $this->secondFactorId;
        $metadata->secondFactorType = $this->secondFactorType;

        return $metadata;
    }

    final public static function deserialize(array $data)
    {
        return new static(
            new IdentityId($data['identity_id']),
            new Institution($data['identity_institution']),
            new SecondFactorId($data['second_factor_id']),
            new SecondFactorType($data['second_factor_type']),
            new IdentityId($data['authority_id'])
        );
    }

    final public function serialize()
    {
        return [
            'identity_id'          => (string) $this->identityId,
            'identity_institution' => (string) $this->identityInstitution,
            'second_factor_id'     => (string) $this->secondFactorId,
            'second_factor_type'   => (string) $this->secondFactorType,
            'authority_id'         => (string) $this->authorityId,
        ];
    }
}
