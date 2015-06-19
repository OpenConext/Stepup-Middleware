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

use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaListingQuery;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RaListingController extends Controller
{
    public function getAction($identityId)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA']);

        $raListing = $this->getService()->findByIdentityId(new IdentityId($identityId));

        if ($raListing === null) {
            throw new NotFoundHttpException(sprintf("RaListing '%s' does not exist", $identityId));
        }

        return new JsonResponse($raListing);
    }

    public function searchAction(Request $request, Institution $institution)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA']);

        $query                 = new RaListingQuery();
        $query->institution    = $institution;
        $query->pageNumber     = (int) $request->get('p', 1);
        $query->orderBy        = $request->get('orderBy');
        $query->orderDirection = $request->get('orderDirection');

        $searchResults = $this->getService()->search($query);

        return JsonCollectionResponse::fromPaginator($searchResults);
    }

    /**
     * @return \Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaListingService
     */
    private function getService()
    {
        return $this->get('surfnet_stepup_middleware_api.service.ra_listing');
    }
}
