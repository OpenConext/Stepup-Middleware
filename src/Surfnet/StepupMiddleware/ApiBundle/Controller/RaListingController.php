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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\RaListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaListingQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaListingService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RaListingController extends AbstractController
{
    public function __construct(
        private readonly RaListingService $raListingService,
        private readonly AuthorizationContextService $authorizationService,
    ) {
    }

    public function get(Request $request, string $identityId, string $institution): JsonResponse
    {
        $this->denyAccessUnlessGrantedOneOff(['ROLE_RA', 'ROLE_READ']);

        $actorIdString = $request->query->get('actorId');
        if (!is_string($actorIdString)) {
            throw new BadRequestHttpException(sprintf('Invalid actorId "%s"', $actorIdString));
        }
        $actorId = new IdentityId($actorIdString);

        $institutionObject = new Institution($institution);

        $authorizationContext = $this->authorizationService->buildInstitutionAuthorizationContext(
            $actorId,
            RegistrationAuthorityRole::raa(),
        );

        $raListing = $this->raListingService->findByIdentityIdAndRaInstitutionWithContext(
            new IdentityId($identityId),
            $institutionObject,
            $authorizationContext,
        );

        if (!$raListing instanceof RaListing) {
            throw new NotFoundHttpException(sprintf("RaListing '%s' does not exist", $identityId));
        }

        return new JsonResponse($raListing);
    }

    /**
     * @return JsonCollectionResponse
     */
    public function search(Request $request): JsonCollectionResponse
    {
        $this->denyAccessUnlessGrantedOneOff(['ROLE_RA', 'ROLE_READ']);

        $actorIdString = $request->query->get('actorId');
        if (!is_string($actorIdString)) {
            throw new BadRequestHttpException(sprintf('Invalid actorId "%s"', $actorIdString));
        }
        $actorId = new IdentityId($actorIdString);

        $query = new RaListingQuery();

        if ($request->query->get('identityId')) {
            $query->identityId = new IdentityId($request->query->get('identityId'));
        }

        if ($request->query->get('institution')) {
            $query->institution = $request->query->get('institution');
        }

        if ($request->query->get('name')) {
            $query->name = $request->query->get('name');
        }

        if ($request->query->get('email')) {
            $query->email = $request->query->get('email');
        }

        if ($request->query->get('role')) {
            $query->role = $request->query->get('role');
        }

        if ($request->query->get('raInstitution')) {
            $query->raInstitution = $request->query->get('raInstitution');
        }

        $query->pageNumber = $request->query->getInt('p', 1);
        $query->orderBy = $request->query->getString('orderBy');
        $query->orderDirection = $request->query->getString('orderDirection');
        $query->authorizationContext = $this->authorizationService->buildInstitutionAuthorizationContext(
            $actorId,
            RegistrationAuthorityRole::raa(),
        );

        $searchResults = $this->raListingService->search($query);

        $filters = $this->raListingService->getFilterOptions($query);

        return JsonCollectionResponse::fromPaginator($searchResults, $filters);
    }
}
