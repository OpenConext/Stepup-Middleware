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

use Surfnet\StepupMiddleware\ApiBundle\Service\DeprovisionServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class DeprovisionController extends AbstractController
{
    private $deprovisionService;

    private $applicationName;

    public function __construct(DeprovisionServiceInterface $deprovisionService, string $applicationName)
    {
        $this->deprovisionService = $deprovisionService;
        $this->applicationName = $applicationName;
    }

    public function deprovisionAction(string $collabPersonId): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_DEPROVISION']);

        $userData = $this->deprovisionService->readUserData($collabPersonId);
        if (!empty($userData)) {
            $this->deprovisionService->deprovision($collabPersonId);
        }
        return new JsonResponse($this->formatResponse('OK', $userData));
    }

    public function dryRunAction(string $collabPersonId): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_DEPROVISION']);
        $userData = $this->deprovisionService->readUserData($collabPersonId);
        return new JsonResponse($this->formatResponse('OK', $userData));
    }

    private function formatResponse(string $status, array $userData): array
    {
        return [
            'status'  => $status,
            'name'    => $this->applicationName,
            'data'    => $userData,
        ];
    }
}
