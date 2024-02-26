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
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Query\RaLocationQuery;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\RaLocationService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class RaLocationController extends AbstractController
{
    /**
     * @return RaLocationService
     */
    private $raLocationService;

    public function __construct(RaLocationService $raLocationService)
    {
        $this->raLocationService = $raLocationService;
    }

    public function searchAction(Request $request, Institution $institution)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS', 'ROLE_READ']);

        $query                 = new RaLocationQuery();
        $query->institution    = $institution;
        $query->orderBy        = $request->get('orderBy', $query->orderBy);
        $query->orderDirection = $request->get('orderDirection', $query->orderDirection);

        $raLocations = $this->raLocationService->search($query);
        $count       = count($raLocations);

        return new JsonCollectionResponse($count, 1, $count, $raLocations);
    }

    public function getAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS', 'ROLE_READ']);

        $raLocationId = new RaLocationId($request->get('raLocationId'));
        $raLocation   = $this->raLocationService->findByRaLocationId($raLocationId);

        return new JsonResponse($raLocation);
    }
}
