<?php

namespace Surfnet\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150508085838 extends AbstractMigration implements ContainerAwareInterface
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
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $gatewaySchema = $this->getGatewaySchema();

        $this->addSql(sprintf('ALTER TABLE %s.second_factor ADD COLUMN uuid VARCHAR(36) DEFAULT NULL', $gatewaySchema));
        $this->addSql(sprintf('UPDATE %s.second_factor set uuid = UUID() WHERE 1 = 1', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor CHANGE id id INT NOT NULL', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP PRIMARY KEY', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP COLUMN id', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor CHANGE uuid id VARCHAR(36) NOT NULL', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor ADD PRIMARY KEY (id)', $gatewaySchema));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $gatewaySchema = $this->getGatewaySchema();

        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP PRIMARY KEY', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor CHANGE id uuid VARCHAR(36)', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor ADD id INT PRIMARY KEY AUTO_INCREMENT', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP COLUMN uuid', $gatewaySchema));
    }

    /**
     * @return string
     */
    private function getGatewaySchema()
    {
        return $this->container->getParameter('database_gateway_name');
    }
}
