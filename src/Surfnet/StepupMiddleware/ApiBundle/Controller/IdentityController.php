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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\IdentityQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonNotFoundResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IdentityController extends AbstractController
{
    public function __construct(
        private readonly IdentityService $identityService,
    ) {
    }

    public function get(string $id): JsonResponse
    {
        $this->denyAccessUnlessGrantedOneOff(['ROLE_RA', 'ROLE_SS', 'ROLE_READ']);

        $identity = $this->identityService->find($id);

        if (!$identity instanceof Identity) {
            throw new NotFoundHttpException(sprintf("Identity '%s' does not exist", $id));
        }

        return new JsonResponse($identity);
    }

    public function collection(Request $request, Institution $institution): JsonCollectionResponse
    {
        $this->denyAccessUnlessGrantedOneOff(['ROLE_RA', 'ROLE_SS', 'ROLE_READ']);

        $query = new IdentityQuery();
        $query->institution = $institution;
        $query->nameId = $request->get('NameID');
        $query->commonName = $request->get('commonName');
        $query->email = $request->get('email');
        $query->pageNumber = (int)$request->get('p', 1);

        $paginator = $this->identityService->search($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }

    public function getRegistrationAuthorityCredentials(string $identityId): JsonResponse
    {
        $this->denyAccessUnlessGrantedOneOff(['ROLE_RA', 'ROLE_SS', 'ROLE_READ']);

        $identityService = $this->identityService;

        $credentials = $identityService->findRegistrationAuthorityCredentialsOf($identityId);

        if (!$credentials instanceof RegistrationAuthorityCredentials) {
            return new JsonNotFoundResponse();
        }

        return new JsonResponse($credentials);
    }
}
