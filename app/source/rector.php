<?php

// rector.php
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Property\CompleteVarDocTypePropertyRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(SetList::DEAD_CODE);
    $containerConfigurator->import(SetList::CODE_QUALITY);
    $services = $containerConfigurator->services();
    $services->set(TypedPropertyRector::class);
    $services->set(ClosureToArrowFunctionRector::class);
    $services->set(\Rector\TypeDeclaration\Rector\FunctionLike\ReturnTypeDeclarationRector::class);
    $services->set(CompleteVarDocTypePropertyRector::class);
};
