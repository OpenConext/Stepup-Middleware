<?php

/**
 * Copyright 2018 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Authorization\Service;

use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionAuthorizationContext;
use Surfnet\StepupMiddleware\ApiBundle\Authorization\Value\InstitutionRoleSetInterface;
use Surfnet\StepupMiddleware\ApiBundle\Exception\InvalidArgumentException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Symfony\Component\HttpFoundation\Request;

/**
 * InstitutionAuthorizationContextFactory
 *
 * Use this factory to build InstitutionAuthorizationContext instances
 * from a GET request with authorization context.
 */
class InstitutionAuthorizationContextFactory implements InstitutionAuthorizationContextFactoryInterface
{
    /**
     * @var IdentityService
     */
    private $identityService;

    public function __construct(IdentityService $identityService)
    {
        $this->identityService = $identityService;
    }

    public function buildFrom(Request $request, InstitutionRoleSetInterface $roleRequirements)
    {
        if ($request->get('actorId') && $request->get('actorInstitution')) {

            $actorId = $request->get('actorId');
            // Retrieve the identity from the service
            $actorIdentity = $this->identityService->find($actorId);

            // The identity of the actor must exist
            if (!$actorIdentity) {
                throw new InvalidArgumentException('Identity with id "%s" could not be found.');
            }

            $institution = $request->get('actorInstitution');
            $actorInstitution = new Institution($institution);

            // The identity that was returned from the service should match the institutio that was provided in the
            // actorInstitution request parameter.
            if (!$actorInstitution->equals($actorIdentity->institution)) {
                throw new InvalidArgumentException(
                    'The institution of the actor did not match that of the institution found in the actor identity.'
                );
            }

            return new InstitutionAuthorizationContext(
                $actorIdentity,
                $actorInstitution,
                $roleRequirements
            );
        }

        return null;
    }
}
