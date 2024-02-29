<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141209150426 extends AbstractMigration implements ContainerAwareInterface
{
    private ?ContainerInterface $container = null;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        $gatewaySchema = $this->getGatewaySchema();
        $middlewareUser = $this->getMiddlewareUser();

        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(
            sprintf(
                'CREATE TABLE %s.second_factor (identity_id VARCHAR(36) NOT NULL, name_id VARCHAR(200) NOT NULL, institution VARCHAR(200) NOT NULL, second_factor_id VARCHAR(36) NOT NULL, second_factor_type VARCHAR(50) NOT NULL, second_factor_identifier VARCHAR(100) NOT NULL, INDEX idx_secondfactor_nameid (name_id), PRIMARY KEY(identity_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
                $gatewaySchema,
            ),
        );
        $this->addSql(
            sprintf(
                'GRANT DELETE,INSERT,SELECT,UPDATE ON %s.second_factor TO %s',
                $gatewaySchema,
                $middlewareUser,
            ),
        );
    }

    public function down(Schema $schema): void
    {
        $gatewaySchema = $this->getGatewaySchema();
        $middlewareUser = $this->getMiddlewareUser();

        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(
            sprintf(
                'REVOKE DELETE,INSERT,SELECT,UPDATE ON %s.second_factor FROM %s',
                $gatewaySchema,
                $middlewareUser,
            ),
        );
        $this->addSql('DROP TABLE second_factor');
    }

    private function getGatewaySchema()
    {
        return $this->container->getParameter('database_gateway_name');
    }

    private function getMiddlewareUser()
    {
        return $this->container->getParameter('database_middleware_user');
    }
}
