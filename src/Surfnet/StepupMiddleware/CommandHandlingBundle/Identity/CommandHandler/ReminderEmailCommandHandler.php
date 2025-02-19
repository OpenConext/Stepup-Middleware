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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler;

use Broadway\CommandHandling\SimpleCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\SendVerifiedSecondFactorRemindersCommand;
use Surfnet\StepupMiddleware\MiddlewareBundle\Service\VerifiedSecondFactorReminderService;

class ReminderEmailCommandHandler extends SimpleCommandHandler
{
    public function __construct(
        private readonly VerifiedSecondFactorReminderService $verifiedSecondFactorReminderService,
    ) {
    }

    public function handleSendVerifiedSecondFactorRemindersCommand(SendVerifiedSecondFactorRemindersCommand $command,): void
    {
        $this->verifiedSecondFactorReminderService->sendReminders(
            $command->requestedAt,
            $command->dryRun,
        );
    }
}
