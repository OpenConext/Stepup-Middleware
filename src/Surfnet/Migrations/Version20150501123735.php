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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150501123735 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform || $this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform),
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql('ALTER TABLE audit_log ADD COLUMN uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('UPDATE audit_log set uuid = UUID() WHERE 1 = 1');
        $this->addSql('ALTER TABLE audit_log CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE audit_log DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE audit_log DROP COLUMN id');
        $this->addSql('ALTER TABLE audit_log CHANGE uuid id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE audit_log ADD PRIMARY KEY (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform || $this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform),
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql('ALTER TABLE audit_log DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE audit_log CHANGE id uuid VARCHAR(36)');
        $this->addSql('ALTER TABLE audit_log ADD id INT PRIMARY KEY AUTO_INCREMENT');
        $this->addSql('ALTER TABLE audit_log DROP COLUMN uuid');
    }
}
