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

use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Controller\AbstractController;
use Surfnet\StepupMiddleware\ApiBundle\Exception\NotFoundException;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Service\VettingTypeHintService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VettingTypeHintController extends AbstractController
{
    public function __construct(
        private readonly VettingTypeHintService $service,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function get(string $institution): JsonResponse
    {
        $this->denyAccessUnlessGrantedOneOff(['ROLE_RA', 'ROLE_SS', 'ROLE_READ']);
        $this->logger->info(sprintf('Received request to get a vetting type hint for institution: %s', $institution));

        try {
            $recoveryToken = $this->service->findBy(new Institution($institution));
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException(
                sprintf("Vetting type hint for institution '%s' was not found", $institution),
                $e,
            );
        }
        return new JsonResponse($recoveryToken);
    }
}
