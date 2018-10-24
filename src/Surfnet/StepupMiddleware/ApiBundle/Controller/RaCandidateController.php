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
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContext;
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
     * @var InstitutionRoleSet
     */
    private $roleRequirements;

    public function __construct(RaCandidateService $raCandidateService)
    {
        $this->raCandidateService = $raCandidateService;

        $this->roleRequirements = new InstitutionRoleSet(
            [new InstitutionRole(InstitutionRole::ROLE_SELECT_RAA)]
        );
    }

    /**
     * @param Institution $actorInstitution
     * @param Request     $request
     * @return JsonCollectionResponse
     */
    public function searchAction(Institution $actorInstitution, Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA']);

        $query                    = new RaCandidateQuery();
        $query->actorInstitution  = $actorInstitution;
        $query->institution       = $request->get('institution');
        $query->commonName        = $request->get('commonName');
        $query->email             = $request->get('email');
        $query->secondFactorTypes = $request->get('secondFactorTypes');
        $query->pageNumber        = (int) $request->get('p', 1);
        $query->authorizationContext = new InstitutionAuthorizationContext($query->actorInstitution, $this->roleRequirements);

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

        $identityId = $request->get('identityId');
        $institution = $request->get('institution');

        $raCandidate = $this->raCandidateService->findByIdentityIdAndRaInstitution($identityId, new Institution($institution));

        if ($raCandidate === null) {
            throw new NotFoundHttpException(sprintf("RaCandidate with IdentityId '%s' does not exist", $identityId));
        }

        return new JsonResponse($raCandidate);
    }
}
