<?php declare(strict_types=1);

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version20221102143350  extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        // Create the new sso_on_2fa option, note the name conversion 'error' made by doctrine.
        $this->addSql('ALTER TABLE institution_configuration_options ADD sso_on2fa_option INT DEFAULT \'0\' NOT NULL');
        // Create the institution_configuration gateway schema
        $gatewaySchema = $this->getGatewaySchema();
        $this->addSql(sprintf('CREATE TABLE %s.institution_configuration (institution VARCHAR(255) NOT NULL, sso_on2fa_enabled TINYINT(1) NOT NULL, PRIMARY KEY(institution)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB', $gatewaySchema));
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        // Down the Middleware schema change
        $this->addSql('ALTER TABLE institution_configuration_options DROP sso_on2fa_option');
        // Gateway schema change (remove the institution_configuration)
        $gatewaySchema = $this->getGatewaySchema();
        $this->addSql(sprintf('DROP TABLE %s.institution_configuration', $gatewaySchema));
    }

    private function getGatewaySchema()
    {
        return $this->container->getParameter('database_gateway_name');
    }
}
