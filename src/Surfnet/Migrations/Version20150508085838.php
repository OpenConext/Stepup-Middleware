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
class Version20150508085838 extends AbstractMigration implements ConfigurationAwareMigrationInterface
{
    use ConfigurationAwareMigrationTrait;

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $gatewaySchema = $this->getGatewaySchema();

        $this->addSql(sprintf('ALTER TABLE %s.second_factor ADD COLUMN uuid VARCHAR(36) DEFAULT NULL', $gatewaySchema));
        $this->addSql(sprintf('UPDATE %s.second_factor set uuid = UUID() WHERE 1 = 1', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor CHANGE id id INT NOT NULL', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP PRIMARY KEY', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP COLUMN id', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor CHANGE uuid id VARCHAR(36) NOT NULL', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor ADD PRIMARY KEY (id)', $gatewaySchema));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $gatewaySchema = $this->getGatewaySchema();

        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP PRIMARY KEY', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor CHANGE id uuid VARCHAR(36)', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor ADD id INT PRIMARY KEY AUTO_INCREMENT', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP COLUMN uuid', $gatewaySchema));
    }
}
