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

use Surfnet\StepupMiddleware\ApiBundle\Response\JsonNotFoundResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SraaController extends Controller
{
    /**
     * @param string $nameId injected by symfony from the request
     * @return JsonNotFoundResponse|JsonResponse
     */
    public function getAction($nameId)
    {
        if (!$this->isGranted('ROLE_RA')) {
            throw new AccessDeniedHttpException('Client is not authorised to access identity');
        }

        $sraa = $this->getService()->findByNameId($nameId);

        if (!$sraa) {
            return new JsonNotFoundResponse();
        }

        return new JsonResponse($sraa);
    }

    /**
     * @return \Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SraaService
     */
    private function getService()
    {
        return $this->get('surfnet_stepup_middleware_api.service.sraa');
    }
}
