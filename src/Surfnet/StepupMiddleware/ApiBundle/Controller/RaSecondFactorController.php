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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Command\SearchRaSecondFactorCommand;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class RaSecondFactorController extends Controller
{
    public function collectionAction(Request $request, Institution $institution)
    {
        if (!$this->isGranted('ROLE_RA')) {
            throw new AccessDeniedHttpException('Client is not authorised to access identity');
        }

        $command = new SearchRaSecondFactorCommand();
        $command->institution = $institution;
        $command->pageNumber = (int) $request->get('p', 1);
        $command->name = $request->get('name');
        $command->type = $request->get('type');
        $command->secondFactorId = $request->get('secondFactorId');
        $command->email = $request->get('email');
        $command->status = $request->get('status');
        $command->orderBy = $request->get('orderBy');
        $command->orderDirection = $request->get('orderDirection');

        $paginator = $this->getService()->search($command);

        return JsonCollectionResponse::fromPaginator($paginator);
    }

    /**
     * @return \Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaSecondFactorService
     */
    private function getService()
    {
        return $this->get('surfnet_stepup_middleware_api.service.ra_second_factor');
    }
}
