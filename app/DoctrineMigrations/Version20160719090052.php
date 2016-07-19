<?php

namespace Surfnet\StepupMiddleware\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Rhumsaa\Uuid\Uuid;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\WhitelistEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\WhitelistEntryRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\TransactionAwarePipeline;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160719090052 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $whitelistEntries = array_map(
            function (WhitelistEntry $whitelistEntry) {
                return $whitelistEntry->institution;
            },
            $this->getWhitelistEntryRepository()->findAll()
        );
        $identities = array_map(
            function (Identity $identity) {
                return $identity->institution;
            },
            $this->getIdentityRepository()->findAll()
        );

        $allInstitutions = array_unique(array_merge($whitelistEntries, $identities));

        $pipeline = $this->getPipeline();

        foreach ($allInstitutions as $institution) {
            $createInstitutionConfigurationCommand = new CreateInstitutionConfigurationCommand();
            $createInstitutionConfigurationCommand->UUID = (string) Uuid::uuid4();
            $createInstitutionConfigurationCommand->institution = $institution->getInstitution();

            $pipeline->process($createInstitutionConfigurationCommand);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // No down-migration needed as the structure has not changed.
    }

    /**
     * @return WhitelistEntryRepository
     */
    private function getWhitelistEntryRepository()
    {
        return $this->container->get('surfnet_stepup_middleware_api.repository.whitelist_entry');
    }

    /**
     * @return IdentityRepository
     */
    private function getIdentityRepository()
    {
        return $this->container->get('surfnet_stepup_middleware_api.repository.identity');
    }

    /**
     * @return TransactionAwarePipeline
     */
    private function getPipeline()
    {
        return $this->container->get('pipeline');
    }
}
