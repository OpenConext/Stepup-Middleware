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

namespace Surfnet\StepupMiddleware\ApiBundle\Request;

use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Exception\BadApiRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ConfigurationInstitutionValueResolver implements ValueResolverInterface
{
    public const INSTITUTION = 'institution';

    /**
     * @return Institution[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();
        if (!$argumentType
            || !is_subclass_of($argumentType, Institution::class, true)
        ) {
            return [];
        }

        return [new Institution($this->getInstitutionFromRequest($request))];
    }

    /**
     * @return string
     */
    private function getInstitutionFromRequest(Request $request): string
    {
        $institution = $request->attributes->get(self::INSTITUTION);
        $request->attributes->remove(self::INSTITUTION);

        if (is_string($institution) && ($institution !== '' && $institution !== '0')) {
            return $institution;
        }

        $institution = $request->query->get(self::INSTITUTION);
        $request->query->remove(self::INSTITUTION);

        if (is_string($institution) && ($institution !== '' && $institution !== '0')) {
            return $institution;
        }

        throw new BadApiRequestException(['This API-call MUST include the institution in the path or query parameters'],);
    }
}
