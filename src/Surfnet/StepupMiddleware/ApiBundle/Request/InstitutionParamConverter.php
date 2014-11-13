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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Surfnet\Stepup\Identity\Value\Institution;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InstitutionParamConverter implements ParamConverterInterface
{
    public function apply(Request $request, ParamConverter $configuration)
    {
        $query = $request->query;
        $institution = $query->get('institution', false);

        if ($institution === false) {
            throw new BadRequestHttpException('This API-call MUST include the institution as get parameter');
        }

        $query->remove('institution');

        $request->attributes->set('institution', new Institution($institution));
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getName() === 'institution'
            && $configuration->getClass() === 'Surfnet\Stepup\Identity\Value\Institution';
    }
}
