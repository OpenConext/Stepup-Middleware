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

use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\VettedSecondFactor;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\VettedSecondFactorQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SecondFactorService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VettedSecondFactorController extends AbstractController
{
    public function __construct(
        private readonly SecondFactorService $secondFactorService,
    ) {
    }

    public function get($id): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS', 'ROLE_READ']);

        $secondFactor = $this->secondFactorService->findVetted(new SecondFactorId($id));

        if (!$secondFactor instanceof VettedSecondFactor) {
            throw new NotFoundHttpException(sprintf("Vetted second factor '%s' does not exist", $id));
        }

        return new JsonResponse($secondFactor);
    }

    public function collection(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS', 'ROLE_READ']);

        $query = new VettedSecondFactorQuery();
        $query->identityId = $request->get('identityId');
        $query->pageNumber = (int)$request->get('p', 1);

        $paginator = $this->secondFactorService->searchVettedSecondFactors($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }
}
