<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/../../src',
        __DIR__ . '/../../tests',
    ])
    ->withSymfonyContainerPhp(__DIR__ . '/../../var/cache/prod/Surfnet_Webauthn_KernelProdContainer.php')
    ->withAttributesSets(symfony: true, doctrine: true)
    ->withPhpSets(php82: true)
    ->withComposerBased(
        symfony: true,
        doctrine: true,
        phpunit: true
    )
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true
    )
    ->withImportNames(
        importNames: true,
        importShortClasses: false
    )
;
