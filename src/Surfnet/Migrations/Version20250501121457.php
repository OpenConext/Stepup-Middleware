<?php

/**
 * Copyright 2025 SURFnet bv
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

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Surfnet\Stepup\MigrationsFactory\ConfigurationAwareMigrationInterface;
use Surfnet\Stepup\MigrationsFactory\ConfigurationAwareMigrationTrait;

final class Version20250501121457 extends AbstractMigration implements ConfigurationAwareMigrationInterface
{
    use ConfigurationAwareMigrationTrait;

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform || $this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform),
            'Migration can only be executed safely on \'mysql\'.',
        );
        // Create the new sso_on_2fa option, note the name conversion 'error' made by doctrine.
        /*
         * The sso_registration_bypass_option enables and disables the "GSSP fallback" option in the Stepup-Gateway for an institution.
         * "GSSP fallback" forwards the second factor authentications at LoA 1.5 to the fallback GSSP when a user does not have
         * any active tokens
         */
        $this->addSql('ALTER TABLE institution_configuration_options ADD sso_registration_bypass_option INT DEFAULT \'0\' NOT NULL');
        // Create the institution_configuration gateway schema
        $gatewaySchema = $this->getGatewaySchema();
        $this->addSql(
            sprintf(
                'ALTER TABLE  %s.institution_configuration ADD sso_registration_bypass TINYINT(1) NOT NULL',
                $gatewaySchema,
            ),
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform || $this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform),
            'Migration can only be executed safely on \'mysql\'.',
        );
        // Down the Middleware schema change
        $this->addSql('ALTER TABLE institution_configuration_options DROP sso_registration_bypass_option');
        // Gateway schema change (remove the institution_configuration)
        $gatewaySchema = $this->getGatewaySchema();
        $this->addSql(sprintf('ALTER TABLE  %s.institution_configuration DROP sso_registration_bypass', $gatewaySchema));
    }
}
