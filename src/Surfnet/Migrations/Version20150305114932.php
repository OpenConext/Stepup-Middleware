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
class Version20150305114932 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql('DROP INDEX idx_ra_second_factor_type ON ra_second_factor');
        $this->addSql('DROP INDEX idx_ra_second_factor_status ON ra_second_factor');
        $this->addSql('DROP INDEX idx_ra_second_factor_name ON ra_second_factor');
        $this->addSql('DROP INDEX idx_ra_second_factor_email ON ra_second_factor');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql('CREATE INDEX idx_ra_second_factor_type ON ra_second_factor (type)');
        $this->addSql('CREATE INDEX idx_ra_second_factor_status ON ra_second_factor (status)');
        $this->addSql('CREATE INDEX idx_ra_second_factor_name ON ra_second_factor (name)');
        $this->addSql('CREATE INDEX idx_ra_second_factor_email ON ra_second_factor (email)');
    }
}
