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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141031164140 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql("ALTER TABLE event_stream CHANGE type type varchar(255)");
        $this->addSql("ALTER TABLE event_stream DROP INDEX unique_playhead");
        $this->addSql("ALTER TABLE event_stream ADD INDEX type (type)");
        $this->addSql("ALTER TABLE event_stream ADD UNIQUE INDEX unique_uuid_playhead (uuid, playhead)");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql("ALTER TABLE event_stream CHANGE type type varchar(150)");
        $this->addSql("ALTER TABLE event_stream DROP INDEX type");
        $this->addSql("ALTER TABLE event_stream DROP INDEX unique_uuid_playhead");
        $this->addSql("ALTER TABLE event_stream ADD INDEX unique_playhead (playhead)");
    }
}
