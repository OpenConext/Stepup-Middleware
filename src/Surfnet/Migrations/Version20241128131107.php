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

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241128131107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE institution_with_ra_locations');
        $this->addSql('ALTER TABLE institution_configuration_options CHANGE number_of_tokens_per_identity_option number_of_tokens_per_identity_option INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE vetted_second_factor CHANGE vetting_type vetting_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_vetted_second_factor_vetting_type ON vetted_second_factor (vetting_type)');
        $this->addSql('ALTER TABLE vetting_type_hint CHANGE hints hints JSON NOT NULL COMMENT \'(DC2Type:stepup_vetting_type_hints)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE institution_with_ra_locations (institution VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, PRIMARY KEY(institution)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE institution_configuration_options CHANGE number_of_tokens_per_identity_option number_of_tokens_per_identity_option TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('DROP INDEX idx_vetted_second_factor_vetting_type ON vetted_second_factor');
        $this->addSql('ALTER TABLE vetted_second_factor CHANGE vetting_type vetting_type VARCHAR(255) DEFAULT \'unknown\'');
        $this->addSql('ALTER TABLE vetting_type_hint CHANGE hints hints LONGTEXT NOT NULL');
    }
}
