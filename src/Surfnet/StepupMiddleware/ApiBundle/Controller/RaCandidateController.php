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

    public function __construct(RaCandidateService $raCandidateService)
    {
        $this->raCandidateService = $raCandidateService;
    }

    /**
     * @param Institution $institution
     * @param Request     $request
     * @return JsonCollectionResponse
     */
    public function searchAction(Institution $institution, Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA']);

        $query                    = new RaCandidateQuery();
        $query->institution       = $institution;
        $query->commonName        = $request->get('commonName');
        $query->email             = $request->get('email');
        $query->secondFactorTypes = $request->get('secondFactorTypes');
        $query->pageNumber        = (int) $request->get('p', 1);

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

        $raCandidate = $this->raCandidateService->findByIdentityId($identityId);

        if ($raCandidate === null) {
            throw new NotFoundHttpException(sprintf("RaCandidate with IdentityId '%s' does not exist", $identityId));
        }

        return new JsonResponse($raCandidate);
    }
}
