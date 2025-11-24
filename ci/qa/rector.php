<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
         __DIR__ . '/../../config',
         __DIR__ . '/../../src',
         __DIR__ . '/../../tests',
         __DIR__ . '/../../templates',
    ])
    // uncomment to reach your current PHP version
//     ->withPhpSets()
    ->withAttributesSets(all: true)
    ->withComposerBased(symfony: true, twig: true, doctrine: true, phpunit: true)
    ->withTypeCoverageLevel(10)
    ->withDeadCodeLevel(10)
    ->withCodeQualityLevel(0)
;
