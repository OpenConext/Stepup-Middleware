<?php

/**
 * Copyright 2018 SURFnet bv
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
class Version20181023151546 extends AbstractMigration
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

        $this->addSql('ALTER TABLE ra_listing DROP PRIMARY KEY');
        $this->addSql(
            'ALTER TABLE ra_listing ADD id INT AUTO_INCREMENT PRIMARY KEY NOT NULL FIRST, ADD ra_institution VARCHAR(255) NOT NULL COMMENT \'(DC2Type:institution)\'',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX idx_ra_listing_unique_identity_institution ON ra_listing (identity_id, ra_institution)',
        );
        $this->addSql('ALTER TABLE ra_candidate DROP PRIMARY KEY');
        $this->addSql(
            'ALTER TABLE ra_candidate ADD ra_institution VARCHAR(255) NOT NULL COMMENT \'(DC2Type:institution)\'',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX idx_ra_candidate_unique_identity_institution ON ra_candidate (identity_id, ra_institution)',
        );
        $this->addSql('ALTER TABLE ra_candidate ADD id INT FIRST');
        $this->addSql('ALTER TABLE ra_candidate ADD INDEX(`id`)');
        $this->addSql('ALTER TABLE ra_candidate CHANGE id id INT NOT NULL AUTO_INCREMENT');
        $this->addSql('ALTER TABLE ra_candidate ADD PRIMARY KEY (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'This migration is irreversible and cannot be reverted due to Fine Grained Authorization BC changes.',
        );

        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform || $this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDBPlatform),
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql('ALTER TABLE ra_candidate MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX idx_ra_candidate_unique_identity_institution ON ra_candidate');
        $this->addSql('ALTER TABLE ra_candidate DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ra_candidate DROP id, DROP ra_institution');
        $this->addSql('ALTER TABLE ra_candidate ADD PRIMARY KEY (identity_id)');
        $this->addSql('ALTER TABLE ra_listing MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX idx_ra_listing_unique_identity_institution ON ra_listing');
        $this->addSql('ALTER TABLE ra_listing DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE ra_listing DROP id, DROP ra_institution');
        $this->addSql('ALTER TABLE ra_listing ADD PRIMARY KEY (identity_id)');
    }

    public function postUp(Schema $schema): void
    {
        $this->connection->executeQuery('UPDATE ra_listing SET ra_institution=institution');
        $this->connection->executeQuery('UPDATE ra_candidate SET ra_institution=institution');
    }
}
