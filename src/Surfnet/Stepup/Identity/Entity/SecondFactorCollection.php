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

use Doctrine\Common\Collections\ArrayCollection;
use Surfnet\Stepup\Exception\LogicException;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\VettingType;
use function array_pop;

final class SecondFactorCollection extends ArrayCollection
{
    public function getSecondFactorWithHighestLoa(SecondFactorTypeService $service): ?SecondFactor
    {
        // We can only get the highest loa'ed second factor when we have a collection of
        // VettedSecondFactors. The because that is the only SF type that has a vetting
        // type, which is required to determine the LoA. As a vetting type can change the
        // LoA.
        $items = $this->toArray();
        if ($items !== [] && array_pop($items) instanceof VettedSecondFactor) {
            return array_reduce(
                $this->toArray(),
                fn(VettedSecondFactor $carry, VettedSecondFactor $item): VettedSecondFactor => $service->hasEqualOrHigherLoaComparedTo(
                    $carry->getType(),
                    new VettingType($carry->vettingType()->type()),
                    $item->getType(),
                    new VettingType($item->vettingType()->type()),
                ) ? $carry : $item,
                $this->first() ?: null,
            );
        }
        throw new LogicException('At this moment, only getting the highest loa SF is supported for a collection of Vetted second factors');
    }
}
