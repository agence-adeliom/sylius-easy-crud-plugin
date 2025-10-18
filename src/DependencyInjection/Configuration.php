<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-suppress UnusedVariable
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('adeliom_sylius_easy_crud');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
