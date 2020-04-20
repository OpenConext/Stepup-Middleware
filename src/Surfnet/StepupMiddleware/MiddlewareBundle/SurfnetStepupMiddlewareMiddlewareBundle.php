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

namespace Surfnet\StepupMiddleware\MiddlewareBundle;

use Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command\BootstrapIdentityWithYubikeySecondFactorCommand;
use Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command\EmailVerifiedSecondFactorRemindersCommand;
use Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command\MigrateInstitutionConfigurationsCommand;
use Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command\MigrationsDiffDoctrineCommand;
use Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command\MigrationsMigrateDoctrineCommand;
use Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command\ReplayEventsCommand;
use Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command\ReplaySpecificEventsCommand;
use Surfnet\StepupMiddleware\MiddlewareBundle\DependencyInjection\CompilerPass\CollectProjectorsForEventReplayCompilerPass;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SurfnetStepupMiddlewareMiddlewareBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CollectProjectorsForEventReplayCompilerPass());
    }

    public function registerCommands(Application $application)
    {
        $application->add(new MigrationsDiffDoctrineCommand());
        $application->add(new MigrationsMigrateDoctrineCommand());
        $application->add(new BootstrapIdentityWithYubikeySecondFactorCommand());
        $application->add(new ReplayEventsCommand());
        $application->add(new MigrateInstitutionConfigurationsCommand());
        $application->add(new ReplaySpecificEventsCommand());
        $application->add(new EmailVerifiedSecondFactorRemindersCommand());
    }
}
