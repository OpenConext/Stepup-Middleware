<?php

/**
 * Copyright 2019 SURFnet bv
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
class Version20190211163604 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql('CREATE INDEX idx_ra_listing_ra_institution ON ra_listing (ra_institution)');
        $this->addSql('CREATE INDEX idx_auditlog_ra_institution ON audit_log (ra_institution)');
        $this->addSql('CREATE INDEX idx_institution ON verified_second_factor (institution)');
        $this->addSql('DROP INDEX id ON ra_candidate');
        $this->addSql('CREATE INDEX idx_ra_institution ON ra_candidate (ra_institution)');
        $this->addSql(
            'CREATE INDEX idx_authorization ON institution_authorization (institution, institution_relation, institution_role)',
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql('DROP INDEX idx_auditlog_ra_institution ON audit_log');
        $this->addSql('DROP INDEX idx_authorization ON institution_authorization');
        $this->addSql('DROP INDEX idx_ra_institution ON ra_candidate');
        $this->addSql('CREATE INDEX id ON ra_candidate (id)');
        $this->addSql('DROP INDEX idx_ra_listing_ra_institution ON ra_listing');
        $this->addSql('DROP INDEX idx_institution ON verified_second_factor');
    }
}
