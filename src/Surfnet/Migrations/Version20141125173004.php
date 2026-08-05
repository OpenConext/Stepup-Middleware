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

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Surfnet\Stepup\MigrationsFactory\ConfigurationAwareMigrationInterface;
use Surfnet\Stepup\MigrationsFactory\ConfigurationAwareMigrationTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141125173004 extends AbstractMigration implements ConfigurationAwareMigrationInterface
{
    use ConfigurationAwareMigrationTrait;

    public function up(Schema $schema): void
    {
        $gatewaySchema = $this->getGatewaySchema();
        $middlewareUser = $this->getMiddlewareUser();

        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform || $this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform),
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(
            sprintf(
                'CREATE TABLE %s.saml_entity (entity_id VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, configuration LONGTEXT NOT NULL, PRIMARY KEY(entity_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
                $gatewaySchema,
            ),
        );
        $this->addSql(
            sprintf("GRANT DELETE,INSERT,SELECT,UPDATE ON %s.saml_entity TO %s", $gatewaySchema, $middlewareUser),
        );
    }

    public function down(Schema $schema): void
    {
        $gatewaySchema = $this->getGatewaySchema();
        $middlewareUser = $this->getMiddlewareUser();

        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform || $this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform),
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(
            sprintf("REVOKE DELETE,INSERT,SELECT,UPDATE ON %s.saml_entity FROM %s", $gatewaySchema, $middlewareUser),
        );
        $this->addSql(sprintf('DROP TABLE %s.saml_entity', $gatewaySchema));
    }
}
