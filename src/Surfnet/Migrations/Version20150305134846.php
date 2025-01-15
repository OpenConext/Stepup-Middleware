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
class Version20150305134846 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql('ALTER TABLE ra_second_factor ADD status_int INT NOT NULL');
        $this->addSql('UPDATE ra_second_factor SET status_int=0 WHERE `status`="unverified"');
        $this->addSql('UPDATE ra_second_factor SET status_int=10 WHERE `status`="verified"');
        $this->addSql('UPDATE ra_second_factor SET status_int=20 WHERE `status`="vetted"');
        $this->addSql('UPDATE ra_second_factor SET status_int=30 WHERE `status`="revoked"');
        $this->addSql('ALTER TABLE ra_second_factor DROP `status`');
        $this->addSql('ALTER TABLE ra_second_factor CHANGE status_int `status` INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(
            'ALTER TABLE ra_second_factor CHANGE `status` `status` VARCHAR(10) NOT NULL COLLATE utf8_unicode_ci',
        );

        $this->addSql('ALTER TABLE ra_second_factor ADD status_string VARCHAR(10) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('UPDATE ra_second_factor SET status_string="unverified" WHERE `status`=0');
        $this->addSql('UPDATE ra_second_factor SET status_string="verified" WHERE `status`=10');
        $this->addSql('UPDATE ra_second_factor SET status_string="vetted" WHERE `status`=20');
        $this->addSql('UPDATE ra_second_factor SET status_string="revoked" WHERE `status`=30');
        $this->addSql('ALTER TABLE ra_second_factor DROP `status`');
        $this->addSql(
            'ALTER TABLE ra_second_factor CHANGE status_string `status` VARCHAR(10) NOT NULL COLLATE utf8_unicode_ci',
        );
    }
}
