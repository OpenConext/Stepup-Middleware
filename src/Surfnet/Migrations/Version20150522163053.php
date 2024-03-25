<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150522163053 extends AbstractMigration implements ContainerAwareInterface
{
    private ?ContainerInterface $container = null;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $gatewaySchema = $this->getGatewaySchema();

        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(sprintf('ALTER TABLE %s.saml_entity ADD id VARCHAR(36) DEFAULT NULL', $gatewaySchema));
        $this->addSql(sprintf('UPDATE %s.saml_entity SET id = UUID() WHERE id IS NULL', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.saml_entity CHANGE id id VARCHAR(36) NOT NULL', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.saml_entity DROP PRIMARY KEY', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.saml_entity ADD PRIMARY KEY (id)', $gatewaySchema));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $gatewaySchema = $this->getGatewaySchema();

        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(sprintf('ALTER TABLE %s.saml_entity DROP PRIMARY KEY', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.saml_entity DROP id', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.saml_entity ADD PRIMARY KEY (entity_id)', $gatewaySchema));
    }

    private function getGatewaySchema(): float|array|bool|int|string|null
    {
        return $this->container->getParameter('database_gateway_name');
    }
}
