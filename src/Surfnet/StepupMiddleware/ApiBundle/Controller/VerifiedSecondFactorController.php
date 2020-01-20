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
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationContextService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\VerifiedSecondFactorOfIdentityQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\VerifiedSecondFactorQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SecondFactorService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VerifiedSecondFactorController extends Controller
{
    /**
     * @var SecondFactorService
     */
    private $secondFactorService;

    /**
     * @var AuthorizationContextService
     */
    private $institutionAuthorizationService;

    public function __construct(
        SecondFactorService $secondFactorService,
        AuthorizationContextService $authorizationService
    ) {
        $this->secondFactorService = $secondFactorService;
        $this->institutionAuthorizationService = $authorizationService;
    }

    public function getAction($id)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS']);

        $secondFactor = $this->secondFactorService->findVerified(new SecondFactorId($id));

        if ($secondFactor === null) {
            throw new NotFoundHttpException(sprintf("Verified second factor '%s' does not exist", $id));
        }

        return new JsonResponse($secondFactor);
    }

    public function collectionAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA']);

        $actorId = new IdentityId($request->get('actorId'));

        $query = new VerifiedSecondFactorQuery();

        if ($request->get('identityId')) {
            $query->identityId = new IdentityId($request->get('identityId'));
        }

        if ($request->get('secondFactorId')) {
            $query->secondFactorId = new SecondFactorId($request->get('secondFactorId'));
        }

        $query->registrationCode = $request->get('registrationCode');
        $query->pageNumber = (int) $request->get('p', 1);
        $query->authorizationContext = $this->institutionAuthorizationService->buildInstitutionAuthorizationContext(
            $actorId,
            new InstitutionRole(InstitutionRole::ROLE_USE_RA)
        );

        $paginator = $this->secondFactorService->searchVerifiedSecondFactors($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }

    public function collectionOfIdentityAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_SS']);
        $query = new VerifiedSecondFactorOfIdentityQuery();

        $query->identityId = new IdentityId($request->get('identityId'));
        $query->pageNumber = (int) $request->get('p', 1);

        $paginator = $this->secondFactorService->searchVerifiedSecondFactorsOfIdentity($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }
}
