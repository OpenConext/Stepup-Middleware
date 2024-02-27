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

use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\ConfiguredInstitutionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ConfiguredInstitutionController extends AbstractController
{
    private ConfiguredInstitutionService $configuredInstitutionService;

    public function __construct(ConfiguredInstitutionService $allListings)
    {
        $this->configuredInstitutionService = $allListings;
    }

    public function collectionAction(): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_READ']);

        $allListings = $this->configuredInstitutionService->getAllAsInstitution();

        return new JsonResponse($allListings);
    }
}
