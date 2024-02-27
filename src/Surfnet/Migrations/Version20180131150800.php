<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Update the Gateway `second_factor` table setting the primary key on the `id` and `identity_id` fields.
 */
class Version20180131150800 extends AbstractMigration implements ContainerAwareInterface
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
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $gatewaySchema = $this->getGatewaySchema();

        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP PRIMARY KEY', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor ADD PRIMARY KEY (id, identity_id)', $gatewaySchema));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $gatewaySchema = $this->getGatewaySchema();

        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP PRIMARY KEY', $gatewaySchema));
        $this->addSql(sprintf('ALTER TABLE %s.second_factor ADD PRIMARY KEY (id)', $gatewaySchema));
    }

    /**
     * @return string
     */
    private function getGatewaySchema()
    {
        return $this->container->getParameter('database_gateway_name');
    }
}
