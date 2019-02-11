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
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\InstitutionAuthorizationService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaListingQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaListingService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RaListingController extends Controller
{
    /**
     * @var RaListingService
     */
    private $raListingService;

    /**
     * @var InstitutionAuthorizationService
     */
    private $authorizationService;

    public function __construct(
        RaListingService $raListingService,
        InstitutionAuthorizationService $authorizationService
    ) {
        $this->raListingService = $raListingService;
        $this->authorizationService = $authorizationService;
    }

    public function getAction(Request $request, $identityId)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA']);

        $institution = $request->get('institution');
        $raListing = $this->raListingService->findByIdentityIdAndRaInstitution(new IdentityId($identityId), new Institution($institution));

        if ($raListing === null) {
            throw new NotFoundHttpException(sprintf("RaListing '%s' does not exist", $identityId));
        }

        return new JsonResponse($raListing);
    }

    /**
     * @param Request $request
     * @param Institution $actorInstitution
     * @return JsonCollectionResponse
     */
    public function searchAction(Request $request, Institution $actorInstitution)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA']);

        $actorId = new IdentityId($request->get('actorId'));

        $query                   = new RaListingQuery();

        if ($request->get('identityId')) {
            $query->identityId = new IdentityId($request->get('identityId'));
        }

        if ($request->get('institution')) {
            $query->institution = $request->get('institution');
        }

        $query->pageNumber       = (int) $request->get('p', 1);
        $query->orderBy          = $request->get('orderBy');
        $query->orderDirection   = $request->get('orderDirection');
        $query->authorizationContext = $this->authorizationService->buildInstitutionAuthorizationContextForManagement(
            $actorId,
            $actorInstitution
        );

        $searchResults = $this->raListingService->search($query);

        return JsonCollectionResponse::fromPaginator($searchResults);
    }
}
