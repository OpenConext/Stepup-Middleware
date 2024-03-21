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

namespace Surfnet\StepupMiddleware\ManagementBundle\Controller;

use DateTime;
use Ramsey\Uuid\Uuid;
use Surfnet\Stepup\Helper\JsonHelper;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\AbstractCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ForgetIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\TransactionAwarePipeline;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RightToBeForgottenController extends AbstractController
{
    public function __construct(
        /**
         * @return TransactionAwarePipeline
         */
        private readonly TransactionAwarePipeline $pipeline,
        private readonly IdentityService $identityService,
    ) {
    }

    public function forgetIdentity(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_MANAGEMENT']);

        $payload = JsonHelper::decode($request->getContent());

        if (!isset($payload['name_id'])) {
            throw new BadRequestHttpException('Please specify a NameID in the property "name_id"');
        }

        if (!isset($payload['institution'])) {
            throw new BadRequestHttpException('Please specify an institution in the property "institution"');
        }

        $this->assertMayForget(new NameId($payload['name_id']), new Institution($payload['institution']));

        $command = new ForgetIdentityCommand();
        $command->UUID = (string)Uuid::uuid4();
        $command->nameId = $payload['name_id'];
        $command->institution = $payload['institution'];

        return $this->handleCommand($request, $command);
    }

    private function handleCommand(Request $request, AbstractCommand $command): JsonResponse
    {
        $this->pipeline->process($command);

        $serverName = $request->server->get('SERVER_NAME') ?: $request->server->get('SERVER_ADDR');

        return new JsonResponse([
            'status' => 'OK',
            'processed_by' => $serverName,
            'applied_at' => (new DateTime())->format(DateTime::ISO8601),
        ]);
    }

    /**
     * @throws ConflictHttpException
     */
    private function assertMayForget(NameId $nameId, Institution $institution): void
    {
        $credentials =
            $this->identityService->findRegistrationAuthorityCredentialsByNameIdAndInstitution($nameId, $institution);

        if ($credentials === null) {
            return;
        }

        if ($credentials->isSraa()) {
            throw new ConflictHttpException(
                'Identity is currently configured to act as an SRAA. ' .
                'Remove its NameID from the configuration and try again.',
            );
        }

        $role = $credentials->isRaa() ? 'RAA' : 'RA';

        throw new ConflictHttpException(
            sprintf(
                'Identity is currently accredited as an %s. Retract the accreditation and try again.',
                $role,
            ),
        );
    }
}
