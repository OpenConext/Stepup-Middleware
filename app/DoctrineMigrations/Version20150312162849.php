<?php

namespace Surfnet\StepupMiddleware\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150312162849 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $gatewaySchema = $this->container->getParameter('database_gateway_name');
        $this->addSql(sprintf(
            'ALTER TABLE %s.second_factor CHANGE second_factor_identifier second_factor_identifier VARCHAR(255) NOT NULL',
            $gatewaySchema
        ));
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $gatewaySchema = $this->container->getParameter('database_gateway_name');
        $this->addSql(sprintf(
            'ALTER TABLE %s.second_factor CHANGE second_factor_identifier second_factor_identifier VARCHAR(36) NOT NULL COLLATE utf8_unicode_ci',
            $gatewaySchema
        ));
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
