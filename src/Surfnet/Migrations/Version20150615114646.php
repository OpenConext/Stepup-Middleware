<?php

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150615114646 extends AbstractMigration implements ContainerAwareInterface
{
    private ?ContainerInterface $container = null;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        $gatewaySchema = $this->getGatewaySchema();
        $middlewareSchema = $this->getMiddlewareSchema();
        $middlewareUser = $this->getMiddlewareUser();

        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(
            sprintf(
                'CREATE TABLE %s.whitelist_entry (institution VARCHAR(255) NOT NULL, PRIMARY KEY(institution)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
                $middlewareSchema,
            ),
        );
        $this->addSql(
            sprintf(
                'CREATE TABLE %s.whitelist_entry (institution VARCHAR(255) NOT NULL, PRIMARY KEY(institution)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
                $gatewaySchema,
            ),
        );
        $this->addSql(
            sprintf(
                'GRANT DELETE,INSERT,SELECT,UPDATE ON %s.whitelist_entry TO %s',
                $gatewaySchema,
                $middlewareUser,
            ),
        );
    }

    public function down(Schema $schema): void
    {
        $gatewaySchema = $this->getGatewaySchema();
        $middlewareSchema = $this->getMiddlewareSchema();
        $middlewareUser = $this->getMiddlewareUser();

        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on \'mysql\'.',
        );

        $this->addSql(
            sprintf(
                'REVOKE DELETE,INSERT,SELECT,UPDATE ON %s.whitelist_entry FROM %s',
                $gatewaySchema,
                $middlewareUser,
            ),
        );
        $this->addSql(sprintf('DROP TABLE %s.saml_entity', $middlewareSchema));
        $this->addSql(sprintf('DROP TABLE %s.saml_entity', $gatewaySchema));
    }

    private function getGatewaySchema(): float|array|bool|int|string|null
    {
        return $this->container->getParameter('database_gateway_name');
    }

    private function getMiddlewareUser(): float|array|bool|int|string|null
    {
        return $this->container->getParameter('database_middleware_user');
    }

    private function getMiddlewareSchema(): float|array|bool|int|string|null
    {
        return $this->container->getParameter('database_middleware_name');
    }
}
