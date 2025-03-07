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
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationContextService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RaCandidateQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaCandidateService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Surfnet\StepupMiddleware\ApiBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function sprintf;

class RaCandidateController extends AbstractController
{
    public function __construct(
        private readonly RaCandidateService $raCandidateService,
        private readonly AuthorizationContextService $authorizationService,
    ) {
    }

    /**
     * @return JsonCollectionResponse
     */
    public function search(Request $request): JsonCollectionResponse
    {
        $this->denyAccessUnlessGrantedOneOff(['ROLE_RA', 'ROLE_READ']);

        $actorId = new IdentityId($request->get('actorId'));

        $query = new RaCandidateQuery();
        $query->institution = $request->get('institution');
        $query->commonName = $request->get('commonName');
        $query->email = $request->get('email');
        $query->secondFactorTypes = $request->get('secondFactorTypes');
        $query->raInstitution = $request->get('raInstitution');
        $query->pageNumber = (int)$request->get('p', 1);

        $query->authorizationContext = $this->authorizationService->buildSelectRaaInstitutionAuthorizationContext(
            $actorId,
        );

        $paginator = $this->raCandidateService->search($query);

        $filters = $this->raCandidateService->getFilterOptions($query);

        return JsonCollectionResponse::fromPaginator($paginator, $filters);
    }

    /**
     * @return JsonResponse
     */
    public function get(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGrantedOneOff(['ROLE_RA', 'ROLE_READ']);

        $actorId = new IdentityId($request->get('actorId'));

        $identityId = $request->get('identityId');

        $authorizationContext = $this->authorizationService->buildInstitutionAuthorizationContext(
            $actorId,
            RegistrationAuthorityRole::ra(),
        );

        $raCandidate = $this->raCandidateService->findOneByIdentityId($identityId);
        if ($raCandidate === null) {
            throw new NotFoundHttpException(sprintf("RaCandidate with IdentityId '%s' does not exist", $identityId));
        }

        // In order to display the correct RA institutions for this ra candidate. We need to display the RA instituions
        // of the actor. But the identity data of the identity. This way we only show the institutions the actor is
        // allowed to make the identity RA(A) for.
        $merged = $this->raCandidateService->setUseRaInstitutionsOnRaCandidate($authorizationContext, $raCandidate);

        return new JsonResponse($merged);
    }
}
