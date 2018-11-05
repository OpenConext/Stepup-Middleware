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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Query\IdentityQuery;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonNotFoundResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IdentityController extends Controller
{
    /**
     * @var IdentityService
     */
    private $identityService;

    /**
     * @var InstitutionRoleSet
     */
    private $roleRequirements;

    public function __construct(
        IdentityService $identityService
    ) {
        $this->identityService = $identityService;

        $this->roleRequirements = new InstitutionRoleSet(
            [new InstitutionRole(InstitutionRole::ROLE_USE_RA), new InstitutionRole(InstitutionRole::ROLE_USE_RAA)]
        );
    }

    public function getAction($id)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS']);

        $identity = $this->identityService->find($id);

        if ($identity === null) {
            throw new NotFoundHttpException(sprintf("Identity '%s' does not exist", $id));
        }

        return new JsonResponse($identity);
    }

    public function collectionAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS']);

        $query = new IdentityQuery();
        $query->institution = $request->get('institution');
        $query->nameId = $request->get('NameID');
        $query->commonName = $request->get('commonName');
        $query->email = $request->get('email');
        $query->pageNumber = (int)$request->get('p', 1);

        if ($query->institution) {
            $query->authorizationContext = new InstitutionAuthorizationContext($query->institution, $this->roleRequirements);
        }

        $paginator = $this->identityService->search($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }

    /**
     * @param string $identityId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getRegistrationAuthorityCredentialsAction($identityId)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS']);

        $identityService = $this->identityService;

        $credentials = $identityService->findRegistrationAuthorityCredentialsOf($identityId);

        if (!$credentials) {
            return new JsonNotFoundResponse();
        }

        return new JsonResponse($credentials);
    }
}
