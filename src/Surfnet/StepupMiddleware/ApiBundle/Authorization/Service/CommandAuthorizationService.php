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
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\RegistrationAuthorityRole;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\IdentityService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\WhitelistService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\RegistrationAuthorityCredentials;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\RaExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfAsserted;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\SelfServiceExecutable;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ExpressLocalePreferenceCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeRegistrantsRecoveryTokenCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeRegistrantsSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\UpdateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VetSecondFactorCommand;

/**
 * Verify if a given command may be executed.
 *
 * The service can be used to test if an institution is on the allow list. If not, the command may not be executed.
 *
 * Three roles are known to the CommandAuthorizationService:
 * 1. SRAA (super registration authority administrator). The admin user to rule them all.
 * 2. RAA (registration authority administrator). Allowed to perform additional administrative commands like selecting
 *    new RA(A)s from a list of candidates.
 * 3. RA (registration authority) the most basic administrative role. Allows for token vetting and revocation.
 *
 * Next, the  maySelfServiceCommandBeExecutedOnBehalfOf and mayRACommandBeExecutedOnBehalfOf methods test if a
 * RA or SS command may be processed by the identity that invoked the command. Some rules are applied here.
 *
 * 1. A SRAA user may always execute the command
 * 2. Certain commands are actionable with a RA role. When the identity is RAA, the identity is also allowed to run
 *    the command.
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class CommandAuthorizationService
{
    public function __construct(
        private readonly WhitelistService $whitelistService,
        private readonly IdentityService $identityService,
        private readonly LoggerInterface $logger,
        private readonly AuthorizationContextService $authorizationContextService,
    ) {
    }

    /**
     * @param IdentityId|null $actorId
     * @return bool
     */
    public function isInstitutionWhitelisted(Institution $institution, IdentityId $actorId = null): bool
    {
        // If the actor is SRAA all actions should be allowed
        if (!is_null($actorId) && $this->isSraa($actorId)) {
            return true;
        }
        return $this->whitelistService->isWhitelisted($institution->getInstitution());
    }

    public function maySelfServiceCommandBeExecutedOnBehalfOf(Command $command, IdentityId $actorId = null): bool
    {
        $commandName = $command::class;
        $identityId = $actorId instanceof IdentityId ? $actorId->getIdentityId() : null;

        // Assert Self Service command could be executed
        if ($command instanceof SelfServiceExecutable) {
            $this->logger->notice('Asserting a SelfService command');

            // If the actor is SRAA all actions should be allowed
            if ($this->isSraa($actorId)) {
                $this->logAllowSelfService(
                    'SRAA user is always allowed to record SelfService commands',
                    $commandName,
                    $identityId,
                );
                return true;
            }

            // Self Asserted token registration is allowed for SelfServiceExecutable commands
            if ($command instanceof SelfAsserted) {
                $this->logAllowSelfService(
                    'Allowing execution of a SelfAsserted command',
                    $commandName,
                    $identityId,
                );
                return true;
            }

            // the CreateIdentityCommand is used to create an Identity for a new user,
            // the UpdateIdentityCommand is used to update name or email of an identity
            // Both are only sent by the SS when the Identity is not logged in yet,
            // thus there is no Metadata::actorInstitution,
            if ($command instanceof CreateIdentityCommand || $command instanceof UpdateIdentityCommand) {
                $this->logAllowSelfService(
                    'Allowing execution of a CreateIdentityCommand or UpdateIdentityCommand command',
                    $commandName,
                    $identityId,
                );
                return true;
            }

            // Validate if the actor is the user
            if ($command->getIdentityId() !== $actorId->getIdentityId()) {
                $this->logDenySelfService(
                    'The actor identity id does not match that of the identity id that was recorded in the command',
                    $commandName,
                    $identityId,
                );
                return false;
            }
        }
        return true;
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity") - To keep the method readable, increased CC is allowed
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    public function mayRaCommandBeExecutedOnBehalfOf(
        Command $command,
        IdentityId $actorId = null,
        Institution $actorInstitution = null,
    ): bool {
        $commandName = $command::class;
        $identityId = $actorId instanceof IdentityId ? $actorId->getIdentityId() : null;

        $this->logger->notice('Running the mayRaCommandBeExecutedOnBehalfOf sequence');
        // Assert RA(A) specific authorizations
        if ($command instanceof RaExecutable) {
            $this->logger->notice('Asserting a RA command');

            // No additional FGA authorization is required for this shared (SS/RA) command
            if ($command instanceof ExpressLocalePreferenceCommand) {
                $this->logAllowRa(
                    'RA(A) is always allowed to perform the ExpressLocalePreferenceCommand',
                    $commandName,
                    $identityId,
                );
                return true;
            }

            // The actor metadata should be set
            if (is_null($actorId) || is_null($actorInstitution)) {
                $this->logDenyRA(
                    'ActorId and/or actorInstitution is missing in mayRaCommandBeExecutedOnBehalfOf',
                    $commandName,
                    $identityId,
                );
                return false;
            }

            // If the actor is SRAA all actions are allowed
            if ($this->isSraa($actorId)) {
                $this->logAllowRa(
                    'SRAA is always allowed to execute RA commands',
                    $commandName,
                    $identityId,
                );
                return true;
            }

            $raInstitution = $command->getRaInstitution();
            if (is_null($raInstitution)) {
                $raInstitution = $actorInstitution->getInstitution();
            }

            $this->logger->notice(sprintf('RA institution = %s', $raInstitution));

            $roleRequirement = RegistrationAuthorityRole::raa();

            // the VetSecondFactorCommand is used to vet a second factor for a user
            // the RevokeRegistrantsSecondFactorCommand is used to revoke a user's secondfactor
            // the RevokeRegistrantsRecoveryTokenCommand is used to revoke a user's recovery token
            // All three are only sent by the RA where the minimal role requirement is RA
            // all the other actions require RAA rights
            if ($command instanceof VetSecondFactorCommand ||
                $command instanceof RevokeRegistrantsSecondFactorCommand ||
                $command instanceof RevokeRegistrantsRecoveryTokenCommand
            ) {
                $this->logger->notice(
                    'VetSecondFactorCommand and RevokeRegistrantsSecondFactorCommand require a RA role',
                );
                $roleRequirement = RegistrationAuthorityRole::ra();
                // Use the institution of the identity (the user vetting or having his token revoked).
                $identity = $this->identityService->find($command->identityId);
                if (!$identity instanceof Identity) {
                    $this->logDenyRA(
                        'Unable to find the identity of the user that is being vetted, or revoked',
                        $commandName,
                        $identityId,
                    );
                    return false;
                }
                $this->logger->notice(
                    sprintf(
                        'Changed RA institution (before %s) to identity institution: %s',
                        $raInstitution,
                        $identity->institution->getInstitution(),
                    ),
                );
                $raInstitution = $identity->institution->getInstitution();
            }

            $authorizationContext = $this->authorizationContextService->buildInstitutionAuthorizationContext(
                $actorId,
                $roleRequirement,
            );

            $this->logger->notice(
                sprintf(
                    'Identity is authorized RA(A) role in institutions: %s',
                    implode(',', $authorizationContext->getInstitutions()->serialize()),
                ),
            );

            if (!$authorizationContext->getInstitutions()->contains(new Institution($raInstitution))) {
                $this->logDenyRA(
                    sprintf(
                        'Identity is not RA(A) for the specified RA institution, "%s". Allowed institutions: "%s"',
                        $raInstitution,
                        implode(',', $authorizationContext->getInstitutions()->serialize()),
                    ),
                    $commandName,
                    $identityId,
                );
                return false;
            }
        }
        $this->logAllowRa(
            'Allowed',
            $commandName,
            $identityId,
        );
        return true;
    }

    private function isSraa(IdentityId $actorId = null): bool
    {
        if (is_null($actorId)) {
            return false;
        }

        $registrationAuthorityCredentials = $this->identityService->findRegistrationAuthorityCredentialsOf(
            $actorId->getIdentityId(),
        );
        if (!$registrationAuthorityCredentials instanceof RegistrationAuthorityCredentials) {
            return false;
        }
        return $registrationAuthorityCredentials->isSraa();
    }

    private function logAllowSelfService(string $message, string $commandName, ?string $identityId): void
    {
        if (!$identityId) {
            $identityId = '"unknown identityId"';
        }

        $this->logger->notice(
            sprintf(
                'Allowing SelfService command %s for identity %s. With message "%s"',
                $commandName,
                $identityId,
                $message,
            ),
        );
    }

    private function logDenySelfService(string $message, string $commandName, ?string $identityId): void
    {
        if (!$identityId) {
            $identityId = '"unknown identityId"';
        }
        $this->logger->notice(
            sprintf(
                'Denying SelfService command %s for identity %s. With message "%s"',
                $commandName,
                $identityId,
                $message,
            ),
        );
    }

    private function logAllowRa(string $message, string $commandName, ?string $identityId): void
    {
        if (!$identityId) {
            $identityId = '"unknown identityId"';
        }
        $this->logger->notice(
            sprintf(
                'Allowing RA command %s for identity %s. With message "%s"',
                $commandName,
                $identityId,
                $message,
            ),
        );
    }

    private function logDenyRA(string $message, string $commandName, ?string $identityId): void
    {
        if (!$identityId) {
            $identityId = '"unknown identityId"';
        }
        $this->logger->error(
            sprintf(
                'Denying RA command %s for identity %s. With message "%s"',
                $commandName,
                $identityId,
                $message,
            ),
        );
    }
}
