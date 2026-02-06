<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/../../src',
        __DIR__ . '/../../tests',
    ]);
    
    // Symfony container voor type resolution
    $rectorConfig->symfonyContainerPhp(__DIR__ . '/../../var/cache/prod/Surfnet_Webauthn_KernelProdContainer.php');
    
    // PHP improvements
    $rectorConfig->sets([
        SetList::PHP_80,
        SetList::PHP_81,
        SetList::PHP_82,
        SetList::DEAD_CODE,
    ]);
    
    // Symfony 7 upgrade sets
    $rectorConfig->sets([
        SymfonySetList::SYMFONY_70,
        SymfonySetList::SYMFONY_71,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ]);
    
    // Doctrine sets
    $rectorConfig->sets([
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
    ]);
    
    // PHPUnit improvements
    $rectorConfig->sets([
        PHPUnitSetList::PHPUNIT_90,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ]);
    
    // Import names
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
};
