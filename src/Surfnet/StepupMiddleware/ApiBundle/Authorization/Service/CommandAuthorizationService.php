<?php
/**
 * Copyright 2010 SURFnet B.V.
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

use Psr\Log\LoggerInterface;
use Surfnet\Stepup\Configuration\Value\InstitutionRole;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\WhitelistService;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\RaExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfAsserted;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfServiceExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ExpressLocalePreferenceCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeRegistrantsSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\UpdateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VetSecondFactorCommand;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CommandAuthorizationService
{

    /**
     * @var WhitelistService
     */
    private $whitelistService;
    /**
     * @var IdentityService
     */
    private $identityService;
    /**
     * @var AuthorizationContextService
     */
    private $authorizationContextService;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        WhitelistService $whitelistService,
        IdentityService $identityService,
        LoggerInterface $logger,
        AuthorizationContextService $authorizationContextService
    ) {
        $this->logger = $logger;
        $this->authorizationContextService = $authorizationContextService;
        $this->whitelistService = $whitelistService;
        $this->identityService = $identityService;
    }

    /**
     * @param Institution $institution
     * @param IdentityId|null $actorId
     * @return bool
     */
    public function isInstitutionWhitelisted(Institution $institution, IdentityId $actorId = null)
    {
        // If the actor is SRAA all actions should be allowed
        if (!is_null($actorId) && $this->isSraa($actorId)) {
            return true;
        }

        if ($this->whitelistService->isWhitelisted($institution->getInstitution())) {
            return true;
        }

        return false;
    }

    /**
     * @param Command $command
     * @param IdentityId|null $actorId
     * @return bool
     */
    public function maySelfServiceCommandBeExecutedOnBehalfOf(Command $command, IdentityId $actorId = null)
    {
        // Assert self service command could be executed
        if ($command instanceof SelfServiceExecutable) {
            $this->logger->notice('Asserting a SelfService command');

            // If the actor is SRAA all actions should be allowed
            if ($this->isSraa($actorId)) {
                return true;
            }

            // Self Asserted token registration is allowed for SelfServiceExecutable commands
            if ($command instanceof SelfAsserted) {
                return true;
            }

            // the CreateIdentityCommand is used to create an Identity for a new user,
            // the UpdateIdentityCommand is used to update name or email of an identity
            // Both are only sent by the SS when the Identity is not logged in yet,
            // thus there is not Metadata::actorInstitution,
            if ($command instanceof CreateIdentityCommand || $command instanceof UpdateIdentityCommand) {
                return true;
            }

            // Validate if the actor is the user
            if ($command->getIdentityId() !== $actorId->getIdentityId()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) - To keep the method readable, increased CC is allowed
     * @param Command $command
     * @param IdentityId|null $actorId
     * @param Institution|null $actorInstitution
     * @return bool
     */
    public function mayRaCommandBeExecutedOnBehalfOf(Command $command, IdentityId $actorId = null, Institution $actorInstitution = null)
    {
        $this->logger->notice('Running the mayRaCommandBeExecutedOnBehalfOf sequence');
        // Assert RAA specific authorizations
        if ($command instanceof RaExecutable) {
            $this->logger->notice('Asserting a RA command');

            // No additional FGA authorization is required for this shared (SS/RA) command
            if ($command instanceof ExpressLocalePreferenceCommand) {
                $this->logger->notice('Ra is allowed to perform ExpressLocalePreferenceCommand');
                return true;
            }

            // The actor metadata should be set
            if (is_null($actorId) || is_null($actorInstitution)) {
                $this->logger->warning('actorId and/or actorInstitution is missing in mayRaCommandBeExecutedOnBehalfOf');
                return false;
            }

            // If the actor is SRAA all actions should be allowed
            if ($this->isSraa($actorId)) {
                $this->logger->notice('SRAA is always allowed');
                return true;
            }

            $raInstitution = $command->getRaInstitution();
            if (is_null($raInstitution)) {
                $raInstitution = $actorInstitution->getInstitution();
            }
            $this->logger->notice(sprintf('RA institution = %s', $raInstitution));

            $role = RegistrationAuthorityRole::raa();

            // the VetSecondFactorCommand is used to vet a second factor for a user
            // the RevokeRegistrantsSecondFactorCommand is used to revoke a user's secondfactor
            // Both are only sent by the RA where the minimal role requirement is RA
            // all the other actions require RAA rights
            if ($command instanceof VetSecondFactorCommand || $command instanceof RevokeRegistrantsSecondFactorCommand) {
                $this->logger->notice('VetSecondFactorCommand and RevokeRegistrantsSecondFactorCommand require a RA role');
                $role = RegistrationAuthorityRole::ra();
                // Use the institution of the identity (the user vetting or having his token revoked).
                $identity = $this->identityService->find($command->identityId);
                if (!$identity) {
                    $this->logger->error('Unable to find the identity of the user that is being vetted, or revoked');
                    return false;
                }
                $this->logger->notice(
                    sprintf(
                        'Changed ra institution (before %s) to identity institution: %s',
                        $raInstitution,
                        $identity->institution->getInstitution()
                    )
                );
                $raInstitution = $identity->institution->getInstitution();
            }

            $authorizationContext = $this->authorizationContextService->buildInstitutionAuthorizationContext(
                $actorId,
                $role
            );

            $this->logger->notice(
                sprintf(
                    'Authorized RA in institutions: %s',
                    implode(',', $authorizationContext->getInstitutions()->serialize())
                )
            );

            if (!$authorizationContext->getInstitutions()->contains(new Institution($raInstitution))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param IdentityId|null $actorId
     * @return bool
     */
    private function isSraa(IdentityId $actorId = null)
    {
        if (is_null($actorId)) {
            return false;
        }

        $registrationAuthorityCredentials = $this->identityService->findRegistrationAuthorityCredentialsOf($actorId->getIdentityId());
        if (!$registrationAuthorityCredentials) {
            return false;
        }

        if (!$registrationAuthorityCredentials->isSraa()) {
            return false;
        }

        return true;
    }
}
