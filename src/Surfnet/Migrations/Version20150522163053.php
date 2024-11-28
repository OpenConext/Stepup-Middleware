<?php

/**
 * Copyright 2015 SURFnet bv
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
class Version20150522163053 extends AbstractMigration implements ConfigurationAwareMigrationInterface
{
    use ConfigurationAwareMigrationTrait;


    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $gatewaySchema = $this->getGatewaySchema();

        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(sprintf('ALTER TABLE %s.saml_entity ADD id VARCHAR(36) DEFAULT NULL', $gatewaySchema));
        $this->addSql(sprintf('UPDATE %s.saml_entity SET id = UUID() WHERE id IS NULL', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.saml_entity CHANGE id id VARCHAR(36) NOT NULL', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.saml_entity DROP PRIMARY KEY', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.saml_entity ADD PRIMARY KEY (id)', $gatewaySchema));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $gatewaySchema = $this->getGatewaySchema();

        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(sprintf('ALTER TABLE %s.saml_entity DROP PRIMARY KEY', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.saml_entity DROP id', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.saml_entity ADD PRIMARY KEY (entity_id)', $gatewaySchema));
    }

}
