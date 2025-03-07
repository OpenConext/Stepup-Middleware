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

namespace Surfnet\Stepup\Identity\Entity;

use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\SecondFactorType;

interface SecondFactor
{
    public function hasEqualOrHigherLoaComparedTo(SecondFactor $comparable, SecondFactorTypeService $service): bool;

    public function hasTypeWithEqualOrLowerLoaComparedTo(
        SecondFactorType $type,
        VettingType $vettingType,
        SecondFactorTypeService $service,
    ): bool;

    public function typeAndIdentifierAreEqual(SecondFactorType $type, SecondFactorIdentifier $identifier): bool;

    public function getType(): SecondFactorType;

    public function getIdentifier(): SecondFactorIdentifier;

    public function vettingType(): VettingType;
}
