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
    public function getAction($id)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS']);

        $identity = $this->getService()->find($id);

        if ($identity === null) {
            throw new NotFoundHttpException(sprintf("Identity '%s' does not exist", $id));
        }

        return new JsonResponse($identity);
    }

    public function collectionAction(Request $request, Institution $institution)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_SS']);

        $query              = new IdentityQuery();
        $query->institution = $institution;
        $query->nameId      = $request->get('NameID');
        $query->commonName  = $request->get('commonName');
        $query->email       = $request->get('email');
        $query->pageNumber  = (int) $request->get('p', 1);

        $paginator = $this->getService()->search($query);

        return JsonCollectionResponse::fromPaginator($paginator);
    }

    /**
     * @param string $identityId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getRegistrationAuthorityCredentialsAction($identityId)
    {
        $identityService = $this->getService();

        $credentials = $identityService->findRegistrationAuthorityCredentialsOf($identityId);

        if (!$credentials) {
            return new JsonNotFoundResponse();
        }

        return new JsonResponse($credentials);
    }

    /**
     * @return IdentityService
     */
    private function getService()
    {
        return $this->get('surfnet_stepup_middleware_api.service.identity');
    }
}
