<?php

/**
 * Copyright 2016 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Controller;

use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\InstitutionWithPersonalRaDetailsService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class InstitutionWithPersonalRaDetailsController extends Controller
{
    public function hasPersonalRaDetailsAction(Request $request, Institution $institution)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS']);

        $personalRaDetailsEnabled = $this->getService()->institutionHasPersonalRaDetails($institution);

        return new JsonResponse($personalRaDetailsEnabled);
    }

    /**
     * @return InstitutionWithPersonalRaDetailsService
     */
    private function getService()
    {
        return $this->container->get('surfnet_stepup_middleware_api.service.institution_with_personal_ra_details');
    }
}