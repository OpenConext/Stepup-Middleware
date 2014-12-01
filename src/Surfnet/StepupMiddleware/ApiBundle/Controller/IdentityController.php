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
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\IdentitySearchSpecification;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchIdentityCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchUnverifiedSecondFactorCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchVerifiedSecondFactorCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SecondFactorService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IdentityController extends Controller
{
    public function getAction($id)
    {
        if (!$this->isGranted('ROLE_RA') && !$this->isGranted('ROLE_SS')) {
            throw new AccessDeniedHttpException('Client is not authorised to access identity');
        }

        $identity = $this->getService()->find($id);

        if ($identity === null) {
            throw new NotFoundHttpException(
                sprintf("Identity '%s' does not exist", $id)
            );
        }

        return new JsonResponse($identity);
    }

    public function collectionAction(Request $request, Institution $institution)
    {
        if (!$this->isGranted('ROLE_RA') && !$this->isGranted('ROLE_SS')) {
            throw new AccessDeniedHttpException('Client is not authorised to access identity');
        }

        $command = new SearchIdentityCommand();
        $command->institution = $institution;
        $command->nameId = $request->get('NameID');
        $command->commonName = $request->get('commonName');
        $command->email = $request->get('email');
        $command->pageNumber = (int) $request->get('p', 1);

        $paginator = $this->getService()->search($command);

        return JsonCollectionResponse::fromPaginator($paginator);
    }

    /**
     * Lists the unverified second factors belonging to the given Identity.
     *
     * @param Request $request
     * @param string $identityId
     * @return Response
     */
    public function findUnverifiedSecondFactorsAction(Request $request, $identityId)
    {
        if (!$this->isGranted('ROLE_RA') && !$this->isGranted('ROLE_SS')) {
            throw new AccessDeniedHttpException('Client is not authorised to access resource');
        }

        $command = new SearchUnverifiedSecondFactorCommand();
        $command->identityId = new IdentityId($identityId);
        $command->pageNumber = (int) $request->get('p', 1);

        $secondFactors = $this->getSecondFactorService()->searchUnverifiedSecondFactors($command);

        return JsonCollectionResponse::fromPaginator($secondFactors);
    }

    /**
     * Lists the verified second factors belonging to the given Identity.
     *
     * @param Request $request
     * @param string $identityId
     * @return Response
     */
    public function findVerifiedSecondFactorsAction(Request $request, $identityId)
    {
        if (!$this->isGranted('ROLE_RA') && !$this->isGranted('ROLE_SS')) {
            throw new AccessDeniedHttpException('Client is not authorised to access resource');
        }

        $command = new SearchVerifiedSecondFactorCommand();
        $command->identityId = new IdentityId($identityId);
        $command->pageNumber = (int) $request->get('p', 1);

        $secondFactors = $this->getSecondFactorService()->searchVerifiedSecondFactors($command);

        return JsonCollectionResponse::fromPaginator($secondFactors);
    }

    /**
     * @return IdentityService
     */
    private function getService()
    {
        return $this->get('surfnet_stepup_middleware_api.service.identity');
    }

    /**
     * @return SecondFactorService
     */
    private function getSecondFactorService()
    {
        return $this->get('surfnet_stepup_middleware_api.service.second_factor');
    }
}
