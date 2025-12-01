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

use Surfnet\Stepup\Helper\SecondFactorProvePossessionHelper;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationContextService;
use Surfnet\StepupMiddleware\ApiBundle\Controller\AbstractController;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VerifiedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\VerifiedSecondFactorOfIdentityQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\VerifiedSecondFactorQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SecondFactorService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class VerifiedSecondFactorController extends AbstractController
{
    public function __construct(
        private readonly SecondFactorService $secondFactorService,
        private readonly AuthorizationContextService $institutionAuthorizationService,
        private readonly SecondFactorProvePossessionHelper $secondFactorProvePossessionHelper,
    ) {
    }

    public function get(string $id): JsonResponse
    {
        $this->denyAccessUnlessGrantedOneOff(['ROLE_RA', 'ROLE_SS', 'ROLE_READ']);

        $secondFactor = $this->secondFactorService->findVerified(new SecondFactorId($id));

        if (!$secondFactor instanceof VerifiedSecondFactor) {
            throw new NotFoundHttpException(sprintf("Verified second factor '%s' does not exist", $id));
        }

        return new JsonResponse($secondFactor);
    }

    public function collection(Request $request): JsonCollectionResponse
    {
        $this->denyAccessUnlessGrantedOneOff(['ROLE_RA', 'ROLE_READ']);

        $actorIdString = $request->query->get('actorId');
        if (!is_string($actorIdString)) {
            throw new BadRequestHttpException(sprintf('Invalid actorId "%s"', $actorIdString));
        }

        $actorId = new IdentityId($actorIdString);

        $query = new VerifiedSecondFactorQuery();

        if ($request->query->get('identityId')) {
            $query->identityId = new IdentityId($request->query->get('identityId'));
        }

        if ($request->query->get('secondFactorId')) {
            $query->secondFactorId = new SecondFactorId($request->query->get('secondFactorId'));
        }

        $query->registrationCode = $request->query->get('registrationCode');
        $query->pageNumber = $request->query->getInt('p', 1);
        $query->authorizationContext = $this->institutionAuthorizationService->buildInstitutionAuthorizationContext(
            $actorId,
            RegistrationAuthorityRole::ra(),
        );

        $paginator = $this->secondFactorService->searchVerifiedSecondFactors($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }

    public function collectionOfIdentity(Request $request): JsonCollectionResponse
    {
        $this->denyAccessUnlessGrantedOneOff(['ROLE_SS', 'ROLE_READ']);
        $query = new VerifiedSecondFactorOfIdentityQuery();

        $identityIdString = $request->query->get('identityId');
        if (!is_string($identityIdString)) {
            throw new BadRequestHttpException(sprintf('Invalid identityId "%s"', $identityIdString));
        }

        $query->identityId = new IdentityId($identityIdString);
        $query->pageNumber = $request->query->getInt('p', 1);

        $paginator = $this->secondFactorService->searchVerifiedSecondFactorsOfIdentity($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }

    public function getCanSkipProvePossession(string $id): JsonResponse
    {
        $this->denyAccessUnlessGrantedOneOff(['ROLE_RA', 'ROLE_READ']);

        $secondFactor = $this->secondFactorService->findVerified(new SecondFactorId($id));

        if (!$secondFactor instanceof VerifiedSecondFactor) {
            throw new NotFoundHttpException(sprintf("Verified second factor '%s' does not exist", $id));
        }

        $secondFactorType = new SecondFactorType($secondFactor->type);

        $skipVetting = $this->secondFactorProvePossessionHelper->canSkipProvePossession($secondFactorType);

        return new JsonResponse($skipVetting);
    }
}
