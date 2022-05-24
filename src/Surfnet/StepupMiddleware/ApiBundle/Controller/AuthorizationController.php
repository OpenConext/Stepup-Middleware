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
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonAuthorizationResponse;
use Surfnet\StepupMiddleware\ApiBundle\Service\DeprovisionServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthorizationController extends AbstractController
{
    public function __construct() {
    }

    public function mayRegisterSelfAssertedTokensAction()
    {
        return new JsonAuthorizationResponse(403, ['Forbidden, go away']);
    }
}
