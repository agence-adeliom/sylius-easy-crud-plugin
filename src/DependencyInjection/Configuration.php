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
        $treeBuilder = new TreeBuilder('sylius_easy_crud');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('attributes')
                    ->info('Declare easy-crud resources via #[AsEasyCrudAdmin] on Admin classes instead of YAML.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->info('Gates both the runtime attribute scan and the maker attribute generation.')
                            ->defaultFalse()
                        ->end()
                        ->arrayNode('paths')
                            ->info('Directories scanned for Admin classes carrying #[AsEasyCrudAdmin].')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
