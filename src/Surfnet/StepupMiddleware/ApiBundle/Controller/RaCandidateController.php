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
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\InstitutionAuthorizationService;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionRoleSet;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaCandidateQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaCandidateService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RaCandidateController extends Controller
{
    /**
     * @var RaCandidateService
     */
    private $raCandidateService;

    /**
     * @var InstitutionAuthorizationService
     */
    private $authorizationService;

    public function __construct(
        RaCandidateService $raCandidateService,
        InstitutionAuthorizationService $authorizationService
    ) {
        $this->raCandidateService = $raCandidateService;
        $this->authorizationService = $authorizationService;
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

        $query                    = new RaCandidateQuery();
        $query->institution       = $request->get('institution');
        $query->commonName        = $request->get('commonName');
        $query->email             = $request->get('email');
        $query->secondFactorTypes = $request->get('secondFactorTypes');
        $query->pageNumber        = (int) $request->get('p', 1);

        $query->authorizationContext = $this->authorizationService->buildInstitutionAuthorizationContextForManagement(
            $actorId,
            $actorInstitution
        );

        $paginator = $this->raCandidateService->search($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA']);

        $actorId = new IdentityId($request->get('actorId'));

        $identityId = $request->get('identityId');
        $institution = $request->get('institution');

        $authorizationContext = $this->authorizationService->buildInstitutionAuthorizationContextForManagement(
            $actorId,
            new Institution($institution)
        );

        $raCandidate = $this->raCandidateService->findByIdentityIdAndRaInstitution($identityId, $authorizationContext);

        if ($raCandidate === null) {
            throw new NotFoundHttpException(sprintf("RaCandidate with IdentityId '%s' does not exist", $identityId));
        }

        return new JsonResponse($raCandidate);
    }
}
