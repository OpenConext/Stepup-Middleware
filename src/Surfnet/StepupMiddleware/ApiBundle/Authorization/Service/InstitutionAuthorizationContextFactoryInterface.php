<?php

/**
 * Copyright 2018 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Authorization\Service;

use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContextInterface;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionRoleSetInterface;
use Symfony\Component\HttpFoundation\Request;

interface InstitutionAuthorizationContextFactoryInterface
{

    /**
     * Searches the Request object for authorization context related
     * parameters. And builds an instance of an
     * InstitutionAuthorizationContextInterface if the required params
     * are present.
     *
     * If they are not, the factory will return null.
     *
     * Institution roles must alo be provided, These will be set on
     * the context for use in the repository later. Each individual
     * GET request should specify what role requirements apply.
     *
     *  The fallback parameter is used as default
     *
     * @param Request $request
     * @param InstitutionRoleSetInterface $roleRequirements
     * @return InstitutionAuthorizationContextInterface|null
     */
    public function buildFrom(Request $request, InstitutionRoleSetInterface $roleRequirements);
}
