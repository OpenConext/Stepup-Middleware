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

use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Service\AuthorizationService;
use Surfnet\StepupMiddleware\ApiBundle\Controller\AbstractController;
use Surfnet\StepupMiddleware\ApiBundle\Response\JsonAuthorizationResponse;

class AuthorizationController extends AbstractController
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
    ) {
    }

    public function mayRegisterSelfAssertedTokens(string $identityId): JsonAuthorizationResponse
    {
        $decision = $this->authorizationService->assertRegistrationOfSelfAssertedTokensIsAllowed(
            new IdentityId($identityId),
        );
        return JsonAuthorizationResponse::from($decision);
    }

    public function mayRegisterRecoveryTokens(string $identityId): JsonAuthorizationResponse
    {
        $decision = $this->authorizationService->assertRecoveryTokensAreAllowed(new IdentityId($identityId));
        return JsonAuthorizationResponse::from($decision);
    }

    public function maySelfVetSelfAssertedToken(string $identityId): JsonAuthorizationResponse
    {
        $decision = $this->authorizationService->assertSelfVetUsingSelfAssertedTokenIsAllowed(
            new IdentityId($identityId),
        );
        return JsonAuthorizationResponse::from($decision);
    }
}
