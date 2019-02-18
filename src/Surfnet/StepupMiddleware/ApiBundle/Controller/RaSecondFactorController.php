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

use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\InstitutionAuthorizationService;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionRoleSet;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaSecondFactorQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaSecondFactorService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class RaSecondFactorController extends Controller
{
    /**
     * @var RaSecondFactorService
     */
    private $raSecondFactorService;

    /**
     * @var InstitutionRoleSet
     */
    private $roleRequirements;
    /**
     * @var InstitutionAuthorizationService
     */
    private $authorizationService;

    public function __construct(
        RaSecondFactorService $raSecondFactorService,
        InstitutionAuthorizationService $authorizationService
    ) {
        $this->raSecondFactorService = $raSecondFactorService;

        $this->roleRequirements = new InstitutionRoleSet(
            [new InstitutionRole(InstitutionRole::ROLE_USE_RA), new InstitutionRole(InstitutionRole::ROLE_USE_RAA)]
        );
        $this->authorizationService = $authorizationService;
    }

    public function collectionAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA']);

        $query = $this->buildRaSecondFactorQuery($request);

        $paginator = $this->raSecondFactorService->search($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }

    public function exportAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA']);

        $query = $this->buildRaSecondFactorQuery($request);

        $results = $this->raSecondFactorService->searchUnpaginated($query);

        return new JsonResponse($results);
    }

    /**
     * @param Request $request
     * @return RaSecondFactorQuery
     */
    private function buildRaSecondFactorQuery(Request $request)
    {
        $actorId = new IdentityId($request->get('actorId'));

        $query = new RaSecondFactorQuery();
        $query->pageNumber = (int)$request->get('p', 1);
        $query->name = $request->get('name');
        $query->type = $request->get('type');
        $query->secondFactorId = $request->get('secondFactorId');
        $query->email = $request->get('email');
        $query->institution = $request->get('institution');
        $query->status = $request->get('status');
        $query->orderBy = $request->get('orderBy');
        $query->orderDirection = $request->get('orderDirection');
        $query->authorizationContext = $this->authorizationService->buildInstitutionAuthorizationContext(
            $actorId,
            $this->roleRequirements
        );

        return $query;
    }
}
