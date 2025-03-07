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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\RaListingService;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonCollectionResponse;
use Surfnet\StepupMiddleware\ApiBundle\Controller\AbstractController;

class RaController extends AbstractController
{
    public function __construct(
        private readonly RaListingService $raListingService,
    ) {
    }

    public function list(Institution $institution): JsonCollectionResponse
    {
        $this->denyAccessUnlessGrantedOneOff(['ROLE_SS', 'ROLE_READ']);

        $registrationAuthorityCredentials = $this->raListingService->listRegistrationAuthoritiesFor($institution);
        $count = count($registrationAuthorityCredentials);

        return new JsonCollectionResponse($count, 1, $count, $registrationAuthorityCredentials);
    }
}
