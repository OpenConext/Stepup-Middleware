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

use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\Stepup\Identity\Value\RecoveryTokenType;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\StepupBundle\Value\SecondFactorType;

final class Metadata
{
    /**
     * @var IdentityId
     */
    public IdentityId $identityId;

    /**
     * @var Institution
     */
    public Institution $identityInstitution;

    /**
     * @var Institution
     */
    public Institution $raInstitution;

    /**
     * @var SecondFactorId|null
     */
    public ?SecondFactorId $secondFactorId;

    /**
     * @var SecondFactorType|null
     */
    public ?SecondFactorType $secondFactorType;

    /**
     * @var SecondFactorIdentifier|null
     */
    public ?SecondFactorIdentifier $secondFactorIdentifier;

    /** @var VettingType */
    public VettingType $vettingType;

    /**
     * @var RecoveryTokenId
     */
    public RecoveryTokenId $recoveryTokenId;

    /**
     * @var RecoveryTokenType
     */
    public RecoveryTokenType $recoveryTokenType;
}
