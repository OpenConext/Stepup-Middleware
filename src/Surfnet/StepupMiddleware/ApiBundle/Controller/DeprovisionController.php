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

use Exception;
use Surfnet\Stepup\Helper\UserDataFormatterInterface;
use Surfnet\StepupMiddleware\ApiBundle\Service\DeprovisionServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class DeprovisionController extends AbstractController
{
    private $deprovisionService;

    private $formatHelper;

    public function __construct(
        DeprovisionServiceInterface $deprovisionService,
        UserDataFormatterInterface $formatHelper
    ) {
        $this->deprovisionService = $deprovisionService;
        $this->formatHelper = $formatHelper;
    }

    public function deprovisionAction(string $collabPersonId): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_DEPROVISION']);
        $errors = [];
        try {
            $userData = $this->deprovisionService->readUserData($collabPersonId);
            if (!empty($userData)) {
                $this->deprovisionService->deprovision($collabPersonId);
            }
        } catch (Exception $e) {
            $userData = [];
            $errors = [$e->getMessage()];
        }
        return new JsonResponse($this->formatHelper->format($userData, $errors));
    }

    public function dryRunAction(string $collabPersonId): JsonResponse
    {
        $this->denyAccessUnlessGranted(['ROLE_DEPROVISION']);
        $errors = [];
        try {
            $userData = $this->deprovisionService->readUserData($collabPersonId);
        } catch (Exception $e) {
            $userData = [];
            $errors = [$e->getMessage()];
        }
        return new JsonResponse($this->formatHelper->format($userData, $errors));
    }
}
