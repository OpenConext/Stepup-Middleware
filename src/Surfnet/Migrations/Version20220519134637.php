<?php declare(strict_types=1);

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
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE institution_configuration_options ADD self_asserted_tokens_option INT DEFAULT \'0\' NOT NULL');
        $this->addSql('CREATE TABLE recovery_token (id VARCHAR(36) NOT NULL, identity_id VARCHAR(36) NOT NULL, type VARCHAR(16) NOT NULL, recovery_method_identifier VARCHAR(255) NOT NULL, INDEX idx_recovery_method_type (type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE identity_self_asserted_token_options (identity_id VARCHAR(36) NOT NULL, possessed_token TINYINT(1) NOT NULL, possessed_self_asserted_token TINYINT(1) NOT NULL, PRIMARY KEY(identity_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE vetted_second_factor ADD vetting_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE recovery_token ADD institution VARCHAR(255) NOT NULL, ADD name VARCHAR(255) NOT NULL, ADD email VARCHAR(255) NOT NULL, ADD status INT NOT NULL');
        $this->addSql('ALTER TABLE audit_log ADD recovery_token_identifier VARCHAR(255) DEFAULT NULL, ADD recovery_token_type VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE TABLE vetting_type_hint (institution VARCHAR(36) NOT NULL, hints LONGTEXT NOT NULL, PRIMARY KEY(institution)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');

        $gatewaySchema = $this->getGatewaySchema();
        $this->addSql(sprintf('ALTER TABLE %s.second_factor ADD vetting_type VARCHAR(255) NOT NULL DEFAULT \'unknown\'', $gatewaySchema));
        $this->addSql(sprintf('update second_factor set vetting_type=\'on-premise\' where true', $gatewaySchema));

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE institution_configuration_options DROP self_asserted_tokens_option');
        $this->addSql('DROP TABLE recovery_token');
        $this->addSql('DROP TABLE identity_self_asserted_token_options');
        $this->addSql('ALTER TABLE vetted_second_factor DROP vetting_type');
        $this->addSql('ALTER TABLE audit_log DROP recovery_token_identifier, DROP recovery_token_type');
        $this->addSql('DROP TABLE vetting_type_hint');

        $gatewaySchema = $this->getGatewaySchema();
        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP vetting_type', $gatewaySchema));
    }

    private function getGatewaySchema()
    {
        return $this->container->getParameter('database_gateway_name');
    }
}
