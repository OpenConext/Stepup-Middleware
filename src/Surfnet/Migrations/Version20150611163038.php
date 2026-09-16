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
class Version20150611163038 extends AbstractMigration
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

        $this->addSql('ALTER TABLE verified_second_factor DROP FOREIGN KEY fk_verified_second_factor_identity');
        $this->addSql('DROP INDEX IDX_7964F91CFF3ED4A8 ON verified_second_factor');
        $this->addSql(
            'ALTER TABLE verified_second_factor ADD institution VARCHAR(255) NOT NULL, ADD common_name VARCHAR(255) NOT NULL, CHANGE identity_id identity_id VARCHAR(36) NOT NULL',
        );
        $this->addSql('ALTER TABLE vetted_second_factor DROP FOREIGN KEY fk_vetted_second_factor_identity');
        $this->addSql('DROP INDEX IDX_29F96B72FF3ED4A8 ON vetted_second_factor');
        $this->addSql('ALTER TABLE unverified_second_factor DROP FOREIGN KEY fk_unverified_second_factor_identity');
        $this->addSql('DROP INDEX IDX_D79226A2FF3ED4A8 ON unverified_second_factor');
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

        $this->addSql(
            'ALTER TABLE unverified_second_factor ADD CONSTRAINT fk_unverified_second_factor_identity FOREIGN KEY (identity_id) REFERENCES identity (id) ON DELETE CASCADE',
        );
        $this->addSql('CREATE INDEX IDX_D79226A2FF3ED4A8 ON unverified_second_factor (identity_id)');
        $this->addSql(
            'ALTER TABLE verified_second_factor DROP institution, DROP common_name, CHANGE identity_id identity_id VARCHAR(36) DEFAULT NULL COLLATE utf8_unicode_ci',
        );
        $this->addSql(
            'ALTER TABLE verified_second_factor ADD CONSTRAINT fk_verified_second_factor_identity FOREIGN KEY (identity_id) REFERENCES identity (id) ON DELETE CASCADE',
        );
        $this->addSql('CREATE INDEX IDX_7964F91CFF3ED4A8 ON verified_second_factor (identity_id)');
        $this->addSql(
            'ALTER TABLE vetted_second_factor ADD CONSTRAINT fk_vetted_second_factor_identity FOREIGN KEY (identity_id) REFERENCES identity (id) ON DELETE CASCADE',
        );
        $this->addSql('CREATE INDEX IDX_29F96B72FF3ED4A8 ON vetted_second_factor (identity_id)');
    }
}
