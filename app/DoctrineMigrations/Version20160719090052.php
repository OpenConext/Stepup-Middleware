<?php

namespace Surfnet\StepupMiddleware\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Rhumsaa\Uuid\Uuid;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\InstitutionListing;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\WhitelistEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\InstitutionListingRepository;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\WhitelistEntryRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\TransactionAwarePipeline;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

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
        $tokenStorage = $this->getTokenStorage();

        // Authenticate as management user, so commands requiring the management role can be sent
        $tokenStorage->setToken(
            new UsernamePasswordToken(
                'management',
                $this->container->getParameter('management_password'),
                'in_memory',
                ['ROLE_MANAGEMENT']
            )
        );

        $whitelistEntryInstitutions = array_map(
            function (WhitelistEntry $whitelistEntry) {
                return $whitelistEntry->institution;
            },
            $this->getWhitelistEntryRepository()->findAll()
        );
        $institutionListingInstitutions = array_map(
            function (InstitutionListing $institutionListing) {
                return $institutionListing->institution;
            },
            $this->getInstitutionListingRepository()->findAll()
        );

        $allInstitutions = array_unique(array_merge($whitelistEntryInstitutions, $institutionListingInstitutions));

        $pipeline = $this->getPipeline();

        foreach ($allInstitutions as $institution) {
            $createInstitutionConfigurationCommand = new CreateInstitutionConfigurationCommand();
            $createInstitutionConfigurationCommand->UUID = (string) Uuid::uuid4();
            $createInstitutionConfigurationCommand->institution = $institution->getInstitution();

            $pipeline->process($createInstitutionConfigurationCommand);
        }

        $tokenStorage->setToken(null);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // No down-migration needed as the structure has not changed.
        $this->write('No down migration executed: this was a data only migration.');
    }

    /**
     * @return WhitelistEntryRepository
     */
    private function getWhitelistEntryRepository()
    {
        return $this->container->get('surfnet_stepup_middleware_api.repository.whitelist_entry');
    }

    /**
     * @return InstitutionListingRepository
     */
    private function getInstitutionListingRepository()
    {
        return $this->container->get('surfnet_stepup_middleware_api.repository.institution_listing');
    }

    /**
     * @return TransactionAwarePipeline
     */
    private function getPipeline()
    {
        return $this->container->get('pipeline');
    }

    /**
     * @return TokenStorage
     */
    private function getTokenStorage()
    {
        return $this->container->get('security.token_storage');
    }
}
