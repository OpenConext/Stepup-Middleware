<?php
/**
 * Copyright 2017 SURFnet bv
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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Service;

use Assert\Assertion;
use DateTime;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\VerifiedSecondFactorRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Value\VerifiedTokenInformation;

/**
 * Retrieves tokens (second factors) that got registered at a given date. That data is then used to send a reminder
 * message to the identity the token belongs to, informing them to vet the token.
 */
class VerifiedSecondFactorReminderService
{
    public function __construct(
        private readonly VerifiedSecondFactorRepository $verifiedRepository,
        private readonly IdentityRepository $identityRepository,
        private readonly VerifiedSecondFactorReminderMailService $mailService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function sendReminders(DateTime $date, bool $dryRun): void
    {
        $this->logger->info(
            sprintf(
                'Sending reminders for date: %s. dry run mode is %s',
                $date->format('Y-m-d'),
                ($dryRun ? 'enabled' : 'disabled'),
            ),
        );

        $totalNumberSent = 0;

        $tokenInformationCollection = $this->buildCollection($date);

        if ($tokenInformationCollection !== []) {
            $this->logger->info(sprintf('%d token reminder(s) will be sent', count($tokenInformationCollection)));

            foreach ($tokenInformationCollection as $tokenInformation) {
                try {
                    $this->mailService->sendReminder($tokenInformation);
                    $wasSent = 1;
                } catch (Exception) {
                    $wasSent = 0;
                }
                $numberSent = $dryRun ? 1 : $wasSent;

                $this->logger->info(
                    sprintf(
                        'Message %s sent %sto "%s" with token id "%s" of type "%s"',
                        ($numberSent === 1 ? 'successfully' : 'was not'),
                        ($dryRun ? 'in dry run mode ' : ''),
                        $tokenInformation->getEmail(),
                        $tokenInformation->getTokenId(),
                        $tokenInformation->getTokenType(),
                    ),
                );
                $totalNumberSent += $numberSent;
            }
        }

        $this->logger->info(
            sprintf(
                '%d reminders %s been sent',
                $totalNumberSent,
                ($dryRun ? 'would have' : 'have'),
            ),
        );
    }

    /**
     * @return VerifiedTokenInformation[]
     */
    private function buildCollection(DateTime $date): array
    {
        $collection = [];

        foreach ($this->verifiedRepository->findByDate($date) as $token) {
            try {
                $identity = $this->identityRepository->find($token->identityId);
                Assertion::isObject(
                    $identity,
                    sprintf(
                        'Identity not found with id "%s" for second factor token "%s"',
                        $token->identityId,
                        $token->id,
                    ),
                );
                $collection[] = VerifiedTokenInformation::fromEntity($token, $identity);
            } catch (InvalidArgumentException $e) {
                $this->logger->alert($e->getMessage());
            }
        }

        return $collection;
    }
}
