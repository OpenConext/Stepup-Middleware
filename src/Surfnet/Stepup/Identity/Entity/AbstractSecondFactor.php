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

use Broadway\EventSourcing\SimpleEventSourcedEntity;
use Surfnet\Stepup\Identity\Value\SecondFactorIdentifier;
use Surfnet\Stepup\Identity\Value\UnknownVettingType;
use Surfnet\Stepup\Identity\Value\VettingType;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupBundle\Value\VettingType as StepupBundleVettingType;

abstract class AbstractSecondFactor extends SimpleEventSourcedEntity implements SecondFactor
{
    public function hasEqualOrHigherLoaComparedTo(SecondFactor $comparable, SecondFactorTypeService $service): bool
    {
        return $comparable->hasTypeWithEqualOrLowerLoaComparedTo($this->getType(), $this->vettingType(), $service);
    }

    public function hasTypeWithEqualOrLowerLoaComparedTo(SecondFactorType $type, VettingType $vettingType, SecondFactorTypeService $service): bool
    {
        // SecondFactorTypeService works with the vetting type value objects
        // from the stepup bundle, so convert them.
        $ownVettingType = new StepupBundleVettingType($this->vettingType()->type());
        $otherVettingType = new StepupBundleVettingType($vettingType->type());

        return $service->hasEqualOrLowerLoaComparedTo($this->getType(), $ownVettingType, $type, $otherVettingType);
    }

    public function typeAndIdentifierAreEqual(SecondFactorType $type, SecondFactorIdentifier $identifier): bool
    {
        $typeIsEqual = $this->getType()->equals($type);
        $identifierIsEqual = $this->getIdentifier()->equals($identifier);
        return $typeIsEqual && $identifierIsEqual;
    }

    /**
     * By default the vetting type of a token is unknown UNITL it has been vetted
     * So only the VettedSecondFactor implementation returns anything other than
     * the UnknownVettingType
     */
    public function vettingType(): VettingType
    {
        return new UnknownVettingType();
    }
}
