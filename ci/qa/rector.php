<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/../../config',
        __DIR__ . '/../../src',
        __DIR__ . '/../../tests',
        __DIR__ . '/../../templates',
    ])
    ->withPhpSets()
    ->withAttributesSets(all: true)
    ->withComposerBased(symfony: true, twig: true, doctrine: true, phpunit: true)
    ->withTypeCoverageLevel(10)
    ->withDeadCodeLevel(10)
    ->withCodeQualityLevel(10)
    ->withSkip([
        ReadOnlyClassRector::class,
        ReadOnlyPropertyRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        RestoreDefaultNullToNullableTypePropertyRector::class,
    ]);
