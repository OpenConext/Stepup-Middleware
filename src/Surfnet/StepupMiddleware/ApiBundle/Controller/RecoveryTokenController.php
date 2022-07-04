<?php

/**
 * Copyright 2022 SURFnet bv
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

use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\StepupMiddleware\ApiBundle\Exception\NotFoundException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RecoveryTokenQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RecoveryTokenService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Exposes the Recovery Tokens projection through the
 * Middleware Identity (read) API
 */
class RecoveryTokenController extends Controller
{
    /**
     * @var RecoveryTokenService
     */
    private $service;

    public function __construct(RecoveryTokenService $recoveryTokenServiceService)
    {
        $this->service = $recoveryTokenServiceService;
    }

    public function getAction($id)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS', 'ROLE_READ']);
        try {
            $recoveryToken = $this->service->get(new RecoveryTokenId($id));
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException(sprintf("Recovery token '%s' does not exist", $id), $e);
        }
        return new JsonResponse($recoveryToken);
    }

    public function collectionAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS', 'ROLE_READ']);

        $query = new RecoveryTokenQuery();
        $query->identityId = $request->get('identityId');
        $query->type = $request->get('type');
        $query->pageNumber = (int) $request->get('p', 1);

        $paginator = $this->service->search($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }
}
