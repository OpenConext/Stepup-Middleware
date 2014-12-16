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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\IdentitySearchSpecification;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchUnverifiedSecondFactorCommand;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SecondFactorService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UnverifiedSecondFactorController extends Controller
{
    public function getAction($id)
    {
        if (!$this->isGranted('ROLE_RA') && !$this->isGranted('ROLE_SS')) {
            throw new AccessDeniedHttpException('Client is not authorised to access unverified second factor');
        }

        $secondFactor = $this->getService()->findUnverified(new SecondFactorId($id));

        if ($secondFactor === null) {
            throw new NotFoundHttpException(
                sprintf("Unverified second factor '%s' does not exist", $id)
            );
        }

        return new JsonResponse($secondFactor);
    }

    public function collectionAction(Request $request)
    {
        if (!$this->isGranted('ROLE_RA') && !$this->isGranted('ROLE_SS')) {
            throw new AccessDeniedHttpException('Client is not authorised to access resource');
        }

        $command = new SearchUnverifiedSecondFactorCommand();
        $command->identityId = $request->get('identityId');
        $command->pageNumber = (int) $request->get('p', 1);

        $paginator = $this->getService()->searchUnverifiedSecondFactors($command);

        return JsonCollectionResponse::fromPaginator($paginator);
    }

    /**
     * @return SecondFactorService
     */
    private function getService()
    {
        return $this->get('surfnet_stepup_middleware_api.service.second_factor');
    }
}
