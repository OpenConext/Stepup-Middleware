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

namespace Surfnet\Stepup\Identity\AuditLog;

use Surfnet\Stepup\Identity\Value\VettingType;

final class Metadata
{
    /**
     * @var \Surfnet\Stepup\Identity\Value\IdentityId
     */
    public $identityId;

    /**
     * @var \Surfnet\Stepup\Identity\Value\Institution
     */
    public $identityInstitution;

    /**
     * @var \Surfnet\Stepup\Identity\Value\Institution
     */
    public $raInstitution;

    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorId|null
     */
    public $secondFactorId;

    /**
     * @var \Surfnet\StepupBundle\Value\SecondFactorType|null
     */
    public $secondFactorType;

    /**
     * @var \Surfnet\Stepup\Identity\Value\SecondFactorIdentifier|null
     */
    public $secondFactorIdentifier;

    /** @var VettingType */
    public $vettingType;

    /**
     * @var \Surfnet\Stepup\Identity\Value\RecoveryTokenId
     */
    public $recoveryTokenId;

    /**
     * @var \Surfnet\Stepup\Identity\Value\RecoveryTokenType
     */
    public $recoveryTokenType;
}
