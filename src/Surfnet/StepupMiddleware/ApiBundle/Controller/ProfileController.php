<?php

/**
 * Copyright 2019 SURFnet B.V.
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
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\ProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function sprintf;

class ProfileController extends Controller
{
    /**
     * @var ProfileService
     */
    private $profileService;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ProfileService $profileService,
        LoggerInterface $logger
    ) {
        $this->profileService = $profileService;
        $this->logger = $logger;
    }

    public function getAction(Request $request, $identityId)
    {
        $this->denyAccessUnlessGranted(['ROLE_RA', 'ROLE_READ']);

        // Is the actor allowed to view the profile page?
        $actorId = $request->get('actorId');
        if ($identityId !== $actorId) {
            throw new AccessDeniedHttpException("Identity and actor id should match. It is not yet allowed to view the profile of somebody else.");
        }
        $this->logger->notice(sprintf('Retrieving profile (autzh) information for IdentityId "%s"', $identityId));

        $profile = $this->profileService->createProfile($identityId);
        if (!$profile) {
            throw new NotFoundHttpException("The profile cannot be created, the identity id did not match an identity.");
        }
        return new JsonResponse($profile);
    }
}
