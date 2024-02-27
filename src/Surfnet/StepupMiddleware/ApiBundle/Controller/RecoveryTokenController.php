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

use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\RecoveryTokenId;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationContextService;
use Surfnet\StepupMiddleware\ApiBundle\Exception\NotFoundException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\RecoveryTokenQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RecoveryTokenService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Exposes the Recovery Tokens projection through the
 * Middleware Identity (read) API
 */
class RecoveryTokenController extends AbstractController
{
    public function __construct(
        private readonly RecoveryTokenService $service,
        private readonly AuthorizationContextService $authorizationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function get($id): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS', 'ROLE_READ']);
        $this->logger->info(sprintf('Received request to get recovery token: %s', $id));

        try {
            $recoveryToken = $this->service->get(new RecoveryTokenId($id));
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException(sprintf("Recovery token '%s' does not exist", $id), $e);
        }
        return new JsonResponse($recoveryToken);
    }

    public function collection(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS', 'ROLE_READ']);
        $this->logger->info(
            sprintf('Received search request for recovery tokens with params: %s', $request->getQueryString()),
        );
        $query = new RecoveryTokenQuery();
        $query->identityId = $request->get('identityId');
        $query->type = $request->get('type');
        $query->status = $request->get('status');
        $query->institution = $request->get('institution');
        $query->email = $request->get('email');
        $query->name = $request->get('name');
        $query->pageNumber = (int)$request->get('p', 1);
        $query->orderBy = $request->get('orderBy');
        $query->orderDirection = $request->get('orderDirection');

        $roles = $this->getUser()->getRoles();
        // Only apply the authorization context on non selfservice requests
        if (!in_array('ROLE_SS', $roles)) {
            $actorId = $request->get('actorId', $request->get('identityId'));
            $this->logger->info(sprintf('Executing query on behalf of %s', $actorId));
            $actorId = new IdentityId($actorId);
            $query->authorizationContext = $this->authorizationService->buildInstitutionAuthorizationContext(
                $actorId,
                RegistrationAuthorityRole::ra(),
            );
        }
        $paginator = $this->service->search($query);
        $this->logger->info(sprintf('Found %d results', $paginator->count()));

        $filters = $this->service->getFilterOptions($query);

        return JsonCollectionResponse::fromPaginator($paginator, $filters);
    }
}
