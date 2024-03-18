<?php

/**
 * Copyright 2016 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupMiddleware\ManagementBundle\Tests\Validator;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Matcher\MatcherAbstract;
use PHPUnit\Framework\TestCase;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Identity\Value\Institution as IdentityInstitution;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\ConfiguredInstitution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Service\ConfiguredInstitutionService;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\WhitelistEntry;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\WhitelistService;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\Constraints\ValidReconfigureInstitutionsRequest;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\ReconfigureInstitutionRequestValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ReconfigureInstitutionRequestValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @return mixed[][]
     */
    public function invalidReconfigureInstitutionRequests(): array
    {
        $dataSet = [];

        foreach (glob(__DIR__ . '/Fixtures/invalid_reconfigure_institution_request/*.php') as $invalidConfiguration) {
            $fixture = include $invalidConfiguration;
            $dataSet[basename($invalidConfiguration)] = [
                $fixture['reconfigureInstitutionRequest'],
                $fixture['expectedPropertyPath'],
                $fixture['expectErrorMessageToContain'],
            ];
        }

        return $dataSet;
    }

    /**
     * @test
     * @group validator
     * @dataProvider invalidReconfigureInstitutionRequests
     * @param array $reconfigureRequest
     * @param string $expectedPropertyPath
     */
    public function it_rejects_invalid_configuration(
        $reconfigureRequest,
        $expectedPropertyPath,
        string $expectErrorMessageToContain,
    ): void {
        $existingInstitution = ConfiguredInstitution::createFrom(new Institution('surfnet.nl'));
        $anotherExistingInstitution = ConfiguredInstitution::createFrom(new Institution('another-organisation.test'));

        $configuredInstitutionServiceMock = Mockery::mock(ConfiguredInstitutionService::class);
        $configuredInstitutionServiceMock
            ->shouldReceive('getAll')
            ->andReturn([
                $existingInstitution,
                $anotherExistingInstitution,
            ]);

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->shouldReceive('addViolation')->with()->once();
        $builder->shouldReceive('atPath')->with($this->spy($actualPropertyPath))->once();

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->shouldReceive('buildViolation')->with($this->spy($actualErrorMessage))->once()->andReturn($builder);

        $secondFactorTypeServiceMock = Mockery::mock(SecondFactorTypeService::class);
        $secondFactorTypeServiceMock->shouldReceive('getAvailableSecondFactorTypes')->andReturn(['yubikey', 'sms']);

        $whitelistedInstitution = WhitelistEntry::createFrom(new IdentityInstitution('surfnet.nl'));
        $whitelistServiceMock = Mockery::mock(WhitelistService::class);
        $whitelistServiceMock->shouldReceive('getAllEntries')->andReturn(new ArrayCollection([$whitelistedInstitution]),);

        $validator = new ReconfigureInstitutionRequestValidator(
            $configuredInstitutionServiceMock,
            $secondFactorTypeServiceMock,
            $whitelistServiceMock,
        );
        $validator->initialize($context);
        $validator->validate($reconfigureRequest, new ValidReconfigureInstitutionsRequest);

        // PHPUnit assertions are more informative than Mockery's method-should-be-called-1-times-but-called-0-times.
        $this->assertEquals(
            $expectedPropertyPath,
            $actualPropertyPath,
            sprintf('Actual path to erroneous property does not match expected path (%s)', $actualErrorMessage),
        );
        $this->assertStringContainsString(
            $expectErrorMessageToContain,
            $actualErrorMessage,
            sprintf(
                'The error message (%s) does not contain the expected message (%s)',
                $actualErrorMessage,
                $expectErrorMessageToContain,
            ),
        );
    }

    /**
     * @test
     * @group validator
     */
    public function reconfigure_institution_request_cannot_contain_institutions_that_do_not_exist(): void
    {
        $existingInstitutions = [];
        $nonExistentInstitution = 'non-existing.organisation.test';
        $expectedErrorMessage = 'Cannot reconfigure non-existent institution';

        $invalidRequest = [$nonExistentInstitution => []];

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->shouldReceive('addViolation')->with()->once();

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->shouldReceive('buildViolation')->once()->with($this->spy($errorMessage))->andReturn($builder);

        $configuredInstitutionServiceMock = Mockery::mock(ConfiguredInstitutionService::class);
        $configuredInstitutionServiceMock
            ->shouldReceive('getAll')
            ->andReturn($existingInstitutions);

        $secondFactorTypeServiceMock = Mockery::mock(SecondFactorTypeService::class);
        $secondFactorTypeServiceMock->shouldReceive('getAvailableSecondFactorTypes')->andReturn(['yubikey', 'sms']);
        $whitelistServiceMock = Mockery::mock(WhitelistService::class);
        $whitelistServiceMock->shouldReceive('getAllEntries')->andReturn(new ArrayCollection([]));

        $validator = new ReconfigureInstitutionRequestValidator(
            $configuredInstitutionServiceMock,
            $secondFactorTypeServiceMock,
            $whitelistServiceMock,
        );
        $validator->initialize($context);

        $validator->validate($invalidRequest, new ValidReconfigureInstitutionsRequest);

        $this->assertStringContainsString($expectedErrorMessage, $errorMessage);
    }

    /**
     * @test
     * @group validator
     */
    public function validation_for_existing_institutions_is_done_case_insensitively(): void
    {
        $existingInstitutions = [ConfiguredInstitution::createFrom(new Institution('surfnet.nl'))];
        $differentlyCasedButSameInstitution = 'Surfnet.nl';

        $invalidRequest = [
            $differentlyCasedButSameInstitution => [
                'use_ra_locations' => false,
                'show_raa_contact_information' => true,
                'verify_email' => false,
                'sso_on_2fa' => false,
                'self_vet' => false,
                'allow_self_asserted_tokens' => false,
                'number_of_tokens_per_identity' => 1,
                'allowed_second_factors' => [],
            ],
        ];

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->shouldNotHaveReceived('addViolation');

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->shouldNotHaveReceived('buildViolation');

        $configuredInstitutionServiceMock = Mockery::mock(ConfiguredInstitutionService::class);
        $configuredInstitutionServiceMock
            ->shouldReceive('getAll')
            ->andReturn($existingInstitutions);
        $secondFactorTypeServiceMock = Mockery::mock(SecondFactorTypeService::class);
        $secondFactorTypeServiceMock->shouldReceive('getAvailableSecondFactorTypes')->andReturn(['yubikey', 'sms']);
        $whitelistServiceMock = Mockery::mock(WhitelistService::class);
        $whitelistServiceMock->shouldReceive('getAllEntries')->andReturn(new ArrayCollection([]));

        $validator = new ReconfigureInstitutionRequestValidator(
            $configuredInstitutionServiceMock,
            $secondFactorTypeServiceMock,
            $whitelistServiceMock,
        );
        $validator->initialize($context);

        $validator->validate($invalidRequest, new ValidReconfigureInstitutionsRequest);

        $this->assertInstanceOf(ConfiguredInstitution::class, $existingInstitutions[0]);
    }

    /**
     * @test
     * @group validator
     */
    public function valid_reconfigure_institution_requests_do_not_cause_any_violations(): void
    {
        $institution = 'surfnet.nl';
        $validRequest = [
            $institution => [
                'use_ra_locations' => true,
                'show_raa_contact_information' => true,
                'verify_email' => true,
                'self_vet' => false,
                'sso_on_2fa' => false,
                'allow_self_asserted_tokens' => false,
                'number_of_tokens_per_identity' => 3,
                'allowed_second_factors' => [],
            ],
        ];

        $existingInstitution = ConfiguredInstitution::createFrom(new Institution($institution));
        $whitelistedInstitution = WhitelistEntry::createFrom(new IdentityInstitution($institution));

        $configuredInstitutionServiceMock = Mockery::mock(ConfiguredInstitutionService::class);
        $configuredInstitutionServiceMock
            ->shouldReceive('getAll')
            ->andReturn([$existingInstitution]);

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->shouldNotHaveReceived('buildViolation');

        $secondFactorTypeServiceMock = Mockery::mock(SecondFactorTypeService::class);
        $secondFactorTypeServiceMock->shouldReceive('getAvailableSecondFactorTypes')->andReturn(['yubikey', 'sms']);
        $whitelistServiceMock = Mockery::mock(WhitelistService::class);
        $whitelistServiceMock->shouldReceive('getAllEntries')->andReturn(new ArrayCollection([$whitelistedInstitution]),);
        $validator = new ReconfigureInstitutionRequestValidator(
            $configuredInstitutionServiceMock,
            $secondFactorTypeServiceMock,
            $whitelistServiceMock,
        );
        $validator->initialize($context);
        $validator->validate($validRequest, new ValidReconfigureInstitutionsRequest);


        $this->assertInstanceOf(ConfiguredInstitution::class, $existingInstitution);
    }

    /**
     * @return MatcherAbstract
     */
    private function spy(mixed &$spy)
    {
        return Mockery::on(
            function ($value) use (&$spy): bool {
                $spy = $value;

                return true;
            },
        );
    }
}
