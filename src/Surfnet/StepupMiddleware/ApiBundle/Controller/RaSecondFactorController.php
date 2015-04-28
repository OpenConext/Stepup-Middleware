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

namespace Surfnet\StepupMiddleware\ApiBundle\Controller;

use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaSecondFactorQuery;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

final class RaSecondFactorController extends Controller
{
    public function collectionAction(Request $request, Institution $institution)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA']);

        $query                 = new RaSecondFactorQuery();
        $query->institution    = $institution;
        $query->pageNumber     = (int) $request->get('p', 1);
        $query->name           = $request->get('name');
        $query->type           = $request->get('type');
        $query->secondFactorId = $request->get('secondFactorId');
        $query->email          = $request->get('email');
        $query->status         = $request->get('status');
        $query->orderBy        = $request->get('orderBy');
        $query->orderDirection = $request->get('orderDirection');

        $paginator = $this->getService()->search($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }

    /**
     * @return \Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaSecondFactorService
     */
    private function getService()
    {
        return $this->get('surfnet_stepup_middleware_api.service.ra_second_factor');
    }
}
