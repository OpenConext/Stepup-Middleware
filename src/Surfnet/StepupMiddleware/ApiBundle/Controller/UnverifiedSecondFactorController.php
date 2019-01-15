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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\UnverifiedSecondFactorQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SecondFactorService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UnverifiedSecondFactorController extends Controller
{
    /**
     * @var SecondFactorService
     */
    private $secondFactorService;

    public function __construct(SecondFactorService $secondFactorService)
    {
        $this->secondFactorService = $secondFactorService;
    }

    public function getAction($id)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS']);

        $secondFactor = $this->secondFactorService->findUnverified(new SecondFactorId($id));

        if ($secondFactor === null) {
            throw new NotFoundHttpException(sprintf("Unverified second factor '%s' does not exist", $id));
        }

        return new JsonResponse($secondFactor);
    }

    public function collectionAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS']);

        $query                    = new UnverifiedSecondFactorQuery();
        $query->identityId        = $request->get('identityId');
        $query->verificationNonce = $request->get('verificationNonce');
        $query->pageNumber        = (int) $request->get('p', 1);

        $paginator = $this->secondFactorService->searchUnverifiedSecondFactors($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }
}
