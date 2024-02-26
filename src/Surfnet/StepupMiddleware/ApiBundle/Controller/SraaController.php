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

use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SraaService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonNotFoundResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class SraaController extends AbstractController
{

    /**
     * @var SraaService
     */
    private $sraaService;

    public function __construct(SraaService $sraaService)
    {
        $this->sraaService = $sraaService;
    }

    /**
     * @param string $nameId injected by symfony from the request
     * @return JsonNotFoundResponse|JsonResponse
     */
    public function getAction($nameId)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_READ']);

        $sraa = $this->sraaService->findByNameId(new NameId($nameId));

        if (!$sraa) {
            return new JsonNotFoundResponse();
        }

        return new JsonResponse($sraa);
    }

    public function listAction() : JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_READ']);

        return new JsonResponse($this->sraaService->findAll());
    }
}
