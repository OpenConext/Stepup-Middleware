<?php

/**
 * Copyright 2020 SURF B.V.
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Service;

use Surfnet\StepupBundle\Value\SecondFactorType;

final class SecondFactorDisplayNameResolverService
{
    /**
     * @var array
     */
    private $secondFactors;

    /**
     * @param array $secondFactors
     */
    public function __construct(array $secondFactors)
    {
        $this->secondFactors = $secondFactors;
    }

    /**
     * @param SecondFactorType $secondFactorType
     *
     * @return string
     */
    public function resolveByType(SecondFactorType $secondFactorType): string
    {
        return $this->secondFactors[(string) $secondFactorType] ?? ucfirst((string) $secondFactorType);
    }
}
