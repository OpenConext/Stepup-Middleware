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
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Query\RaLocationQuery;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\RaLocationService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

final class RaLocationController extends Controller
{
    public function listAction(Request $request, Institution $institution)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS']);

        $query                 = new RaLocationQuery();
        $query->institution    = $institution;
        $query->orderBy        = $request->get('orderBy', $query->orderBy);
        $query->orderDirection = $request->get('orderDirection', $query->orderDirection);

        $raLocations = $this->getRaLocationService()->search($query);
        $count = count($raLocations);

        return new JsonCollectionResponse($count, 1, $count, $raLocations);
    }

    /**
     * @return RaLocationService
     */
    private function getRaLocationService()
    {
        return $this->container->get('surfnet_stepup_middleware_api.service.ra_location');
    }
}
