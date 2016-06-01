<?php

/**
 * Copyright 2014 SURFnet bv
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

use Mockery as m;
use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\ConfigurationStructureValidator;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\Constraints\HasValidConfigurationStructure;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\EmailTemplatesConfigurationValidator;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\GatewayConfigurationValidator;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\IdentityProviderConfigurationValidator;
use Surfnet\StepupMiddleware\ManagementBundle\Validator\ServiceProviderConfigurationValidator;

final class ConfigurationValidationTest extends TestCase
{
    public function invalidConfigurations()
    {
        $dataSet = [];

        foreach (glob(__DIR__ . '/Fixtures/invalid_configuration/*.php') as $invalidConfiguration) {
            $fixture = include $invalidConfiguration;
            $dataSet[basename($invalidConfiguration)] = [
                $fixture['configuration'],
                $fixture['expectedPropertyPath']
            ];
        };

        return $dataSet;
    }

    /**
     * @test
     * @group command-handler
     * @dataProvider invalidConfigurations
     * @param array $configuration
     * @param string $expectedPropertyPath
     */
    public function it_rejects_invalid_configuration($configuration, $expectedPropertyPath)
    {
        $builder = m::mock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
        $builder->shouldReceive('addViolation')->with()->once();
        $builder->shouldReceive('atPath')->with(self::spy($actualPropertyPath))->once();

        $context = m::mock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $context->shouldReceive('buildViolation')->with(self::spy($errorMessage))->once()->andReturn($builder);

        $validator = new ConfigurationStructureValidator(
            new GatewayConfigurationValidator(
                new IdentityProviderConfigurationValidator(),
                new ServiceProviderConfigurationValidator()
            ),
            new EmailTemplatesConfigurationValidator('en_GB')
        );
        $validator->initialize($context);
        $validator->validate(json_encode($configuration), new HasValidConfigurationStructure());

        // PHPUnit assertions are more informative than Mockery's method-should-be-called-1-times-but-called-0-times.
        $this->assertEquals(
            $expectedPropertyPath,
            $actualPropertyPath,
            sprintf("Actual path to erroneous property doesn't match expected path (%s)", $errorMessage)
        );
    }

    /**
     * @param mixed &$spy
     * @return \Mockery\Matcher\MatcherAbstract
     */
    private static function spy(&$spy)
    {
        return m::on(
            function ($value) use (&$spy) {
                $spy = $value;

                return true;
            }
        );
    }
}
