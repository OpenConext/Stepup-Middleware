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

namespace Surfnet\StepupMiddleware\ManagementBundle\Controller;

use Assert\InvalidArgumentException;
use DateTime;
use Exception;
use GuzzleHttp;
use InvalidArgumentException as CoreInvalidArgumentException;
use Rhumsaa\Uuid\Uuid;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Command\Command;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\UpdateConfigurationCommand;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ConfigurationController extends Controller
{
    public function updateAction(Request $request)
    {
        try {
            $decodedConfiguration = GuzzleHttp\json_decode($request->getContent(), true);
            $this->getConfigurationValidator()->validate($decodedConfiguration, '');
        } catch (InvalidArgumentException $assertionException) {
            // Assertion Error
            $errors[$assertionException->getPropertyPath()] = $assertionException->getMessage();

            $response = new JsonResponse();
            $response
                // EntityIDs are almost always URLs. Escaping forward slashes is done for ease of use of json within
                // <script> tags, which is not done here. This increases readability and searching of errors.
                // hence we allow unescaped slashes.
                ->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_SLASHES)
                ->setData(['configuration-errors' => $errors])
                ->setStatusCode(400);

            return $response;
        } catch (CoreInvalidArgumentException $e) {
            // Guzzlehttp/json_decode error (malformed json)
            $response = new JsonResponse(['json-errors' => [$e->getMessage()]], 400);

            return $response;
        } catch (Exception $e) {
            // any other errors
            /** @var \Monolog\Logger $logger */
            $logger  = $this->get('logger');
            $context = ['location' => $e->getFile() . '::' . $e->getFile(), 'trace' => $e->getTraceAsString()];
            $logger->critical($e->getMessage(), $context);

            return new JsonResponse(['Internal Server Error'], 500);
        }

        $command = new UpdateConfigurationCommand();
        $command->UUID = Uuid::uuid4();
        $command->configuration = $request->getContent();

        return $this->handleCommand($request, $command);
    }

    /**
     * @param Request $request
     * @param Command $command
     * @return JsonResponse
     */
    private function handleCommand(Request $request, Command $command)
    {
        /** @var \Surfnet\StepupMiddleware\CommandHandlingBundle\Pipeline\Pipeline $pipeline */
        $pipeline = $this->get('pipeline');
        $pipeline->process($command);

        $serverName = $request->server->get('SERVER_NAME') ?: $request->server->get('SERVER_ADDR');
        $response   = new JsonResponse([
            'status'       => 'OK',
            'processed_by' => $serverName,
            'applied_at'   => (new DateTime())->format('c')
        ]);

        return $response;
    }

    /**
     * @return \Surfnet\StepupMiddleware\ManagementBundle\Validator\ConfigurationValidator
     */
    private function getConfigurationValidator()
    {
        return $this->get('surfnet_stepup_middleware_management.validator.configuration');
    }
}
