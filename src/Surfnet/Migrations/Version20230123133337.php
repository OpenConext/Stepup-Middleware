<?php declare(strict_types=1);

namespace Surfnet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Surfnet\Stepup\Identity\Value\VettingType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Prepare middleware for the pending Self-asserted tokens feature. One new column must be added
 * before releasing the SAT feature. This to ensure the ability of a rollback to the previous
 * version without database schema issues.
 *
 * See the explanation below why the on_premise default value is set on the vetting type column
 */
final class Version20230123133337 extends AbstractMigration implements ContainerAwareInterface
{

    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {

        $gatewaySchema = $this->getGatewaySchema();
        $this->addSql(
            sprintf(
                'ALTER TABLE %s.second_factor ADD identity_vetted VARCHAR(255) NOT NULL DEFAULT \'1\'',
                $gatewaySchema
            )
        );
    }

    public function down(Schema $schema): void
    {
        $gatewaySchema = $this->getGatewaySchema();
        $this->addSql(sprintf('ALTER TABLE %s.second_factor DROP vetting_type', $gatewaySchema));
    }

    private function getGatewaySchema()
    {
        return $this->container->getParameter('database_gateway_name');
    }
}
