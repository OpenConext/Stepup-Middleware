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
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141210174213 extends AbstractMigration implements ContainerAwareInterface
{
    private ?ContainerInterface $container = null;

    public function up(Schema $schema): void
    {
        $gatewaySchema = $this->getGatewaySchema();

        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP PRIMARY KEY', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor ADD id INT NOT NULL FIRST', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor ADD PRIMARY KEY (id)', $gatewaySchema));
        $this->addSql(
            sprintf('ALTER TABLE %s.second_factor CHANGE id id INT AUTO_INCREMENT NOT NULL FIRST', $gatewaySchema),
        );
    }

    public function down(Schema $schema): void
    {
        $gatewaySchema = $this->getGatewaySchema();

        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP PRIMARY KEY', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP id', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor ADD PRIMARY KEY (identity_id)', $gatewaySchema));
    }

    private function getGatewaySchema(): float|array|bool|int|string|null
    {
        return $this->container->getParameter('database_gateway_name');
    }

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }
}
