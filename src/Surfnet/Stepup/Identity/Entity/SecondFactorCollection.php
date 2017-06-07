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
use Surfnet\StepupBundle\Service\SecondFactorTypeService;

final class SecondFactorCollection extends ArrayCollection
{
    /**
     * @param SecondFactorTypeService $service
     * @return null|SecondFactor
     */
    public function getSecondFactorWithHighestLoa(SecondFactorTypeService $service)
    {
        return array_reduce(
            $this->toArray(),
            function (SecondFactor $carry, SecondFactor $item) use ($service) {
                return $service->hasEqualOrHigherLoaComparedTo($carry->getType(), $item->getType()) ? $carry : $item;
            },
            $this->first() ?: null
        );
    }
}
