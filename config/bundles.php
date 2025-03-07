<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    JMS\TranslationBundle\JMSTranslationBundle::class => ['all' => true],
    Nelmio\SecurityBundle\NelmioSecurityBundle::class => ['all' => true],
    OpenConext\MonitorBundle\OpenConextMonitorBundle::class => ['all' => true],
    Surfnet\SamlBundle\SurfnetSamlBundle::class => ['all' => false],
    Surfnet\StepupBundle\SurfnetStepupBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Liip\TestFixturesBundle\LiipTestFixturesBundle::class => ['dev' => true, 'test' => true, 'smoketest' => true, 'smoketest_event_replay' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class => ['dev' => true, 'test' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true, 'smoketest' => true, 'dev_event_replay' => true, 'prod_event_replay' => true, 'smoketest_event_replay' => true],
    Surfnet\StepupMiddleware\ApiBundle\SurfnetStepupMiddlewareApiBundle::class => ['all' => true],
    Surfnet\StepupMiddleware\CommandHandlingBundle\SurfnetStepupMiddlewareCommandHandlingBundle::class => ['all' => true],
    Surfnet\StepupMiddleware\GatewayBundle\SurfnetStepupMiddlewareGatewayBundle::class => ['all' => true],
    Surfnet\StepupMiddleware\ManagementBundle\SurfnetStepupMiddlewareManagementBundle::class => ['all' => true],
    Surfnet\StepupMiddleware\MiddlewareBundle\SurfnetStepupMiddlewareMiddlewareBundle::class => ['all' => true],
];
