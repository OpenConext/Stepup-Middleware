<?php

/**
 * Copyright 2022 SURFnet bv
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
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the Self asserted tokens feature to the middleware and gateway databases
 */
final class Version20220519134637 extends AbstractMigration implements ContainerAwareInterface
{
    private ?ContainerInterface $container = null;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );
        $this->addSql(
            'ALTER TABLE institution_configuration_options ADD self_asserted_tokens_option INT DEFAULT \'0\' NOT NULL',
        );
        $this->addSql(
            'CREATE TABLE recovery_token (id VARCHAR(36) NOT NULL, identity_id VARCHAR(36) NOT NULL, type VARCHAR(16) NOT NULL, recovery_method_identifier VARCHAR(255) NOT NULL, INDEX idx_recovery_method_type (type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB',
        );
        $this->addSql(
            'CREATE TABLE identity_self_asserted_token_options (identity_id VARCHAR(36) NOT NULL, possessed_token TINYINT(1) NOT NULL, possessed_self_asserted_token TINYINT(1) NOT NULL, PRIMARY KEY(identity_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB',
        );
        // The unknown vetting type is set on the vetted_second_factor::vetting_type column for the existing second
        // factors. This to inform consumers of the projection, that the vetting type was recorded at a time before we
        // tracked the vetting type of the vetted second factors. It is safe to assume the vetting type is either
        // on-premise or self-vetted (both vetting types where the identity of the user was verified at the service desk
        // at some point).
        $this->addSql('ALTER TABLE vetted_second_factor ADD vetting_type VARCHAR(255) DEFAULT \'unknown\'');
        $this->addSql(
            'ALTER TABLE recovery_token ADD institution VARCHAR(255) NOT NULL, ADD name VARCHAR(255) NOT NULL, ADD email VARCHAR(255) NOT NULL, ADD status INT NOT NULL',
        );
        $this->addSql(
            'ALTER TABLE audit_log ADD recovery_token_identifier VARCHAR(255) DEFAULT NULL, ADD recovery_token_type VARCHAR(36) DEFAULT NULL',
        );
        $this->addSql(
            'CREATE TABLE vetting_type_hint (institution VARCHAR(36) NOT NULL, hints LONGTEXT NOT NULL, PRIMARY KEY(institution)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB',
        );

        $gatewaySchema = $this->getGatewaySchema();
        $this->addSql(
            sprintf(
                'ALTER TABLE %s.second_factor ADD identity_vetted TINYINT(1) DEFAULT \'1\'',
                $gatewaySchema,
            ),
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );
        $this->addSql('ALTER TABLE institution_configuration_options DROP self_asserted_tokens_option');
        $this->addSql('DROP TABLE recovery_token');
        $this->addSql('DROP TABLE identity_self_asserted_token_options');
        $this->addSql('ALTER TABLE vetted_second_factor DROP vetting_type');
        $this->addSql('ALTER TABLE audit_log DROP recovery_token_identifier, DROP recovery_token_type');
        $this->addSql('DROP TABLE vetting_type_hint');

        $gatewaySchema = $this->getGatewaySchema();
        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP identity_vetted', $gatewaySchema));
    }

    private function getGatewaySchema(): float|array|bool|int|string|null
    {
        return $this->container->getParameter('database_gateway_name');
    }
}
