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
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationContextService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaListingQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaListingService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RaListingController extends AbstractController
{
    private RaListingService $raListingService;

    private AuthorizationContextService $authorizationService;

    public function __construct(
        RaListingService $raListingService,
        AuthorizationContextService $authorizationService
    ) {
        $this->raListingService = $raListingService;
        $this->authorizationService = $authorizationService;
    }

    public function getAction(Request $request, $identityId): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_READ']);

        $actorId = new IdentityId($request->get('actorId'));
        $institution = new Institution($request->get('institution'));

        $authorizationContext = $this->authorizationService->buildInstitutionAuthorizationContext(
            $actorId,
            RegistrationAuthorityRole::raa()
        );

        $raListing = $this->raListingService->findByIdentityIdAndRaInstitutionWithContext(
            new IdentityId($identityId),
            $institution,
            $authorizationContext
        );

        if ($raListing === null) {
            throw new NotFoundHttpException(sprintf("RaListing '%s' does not exist", $identityId));
        }

        return new JsonResponse($raListing);
    }

    /**
     * @param Request $request
     * @return JsonCollectionResponse
     */
    public function search(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_READ']);

        $actorId = new IdentityId($request->get('actorId'));

        $query = new RaListingQuery();

        if ($request->get('identityId')) {
            $query->identityId = new IdentityId($request->get('identityId'));
        }

        if ($request->get('institution')) {
            $query->institution = $request->get('institution');
        }

        if ($request->get('name')) {
            $query->name = $request->get('name');
        }

        if ($request->get('email')) {
            $query->email = $request->get('email');
        }

        if ($request->get('role')) {
            $query->role = $request->get('role');
        }

        if ($request->get('raInstitution')) {
            $query->raInstitution = $request->get('raInstitution');
        }

        $query->pageNumber = (int)$request->get('p', 1);
        $query->orderBy = $request->get('orderBy');
        $query->orderDirection = $request->get('orderDirection');
        $query->authorizationContext = $this->authorizationService->buildInstitutionAuthorizationContext(
            $actorId,
            RegistrationAuthorityRole::raa()
        );

        $searchResults = $this->raListingService->search($query);

        $filters = $this->raListingService->getFilterOptions($query);

        return JsonCollectionResponse::fromPaginator($searchResults, $filters);
    }
}
