<?php

/**
 * Copyright 2024 SURFnet bv
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

declare(strict_types=1);

namespace Surfnet\Stepup\MigrationsFactory;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;

class ConfigurationMigrationFactory implements MigrationFactory
{
    public function __construct(
        private MigrationFactory $migrationFactory,
        private string           $gatewaySchema,
        private string           $middlewareSchema,
        private string           $middlewareUser,
    ) {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $migration = $this->migrationFactory->createVersion($migrationClassName);

        if ($migration instanceof ConfigurationAwareMigrationInterface) {
            $migration->setConfiguration($this->gatewaySchema, $this->middlewareSchema, $this->middlewareUser);
        }

        return $migration;
    }
}
