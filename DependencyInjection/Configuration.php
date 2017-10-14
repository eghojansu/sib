<?php

namespace Eghojansu\Bundle\SetupBundle\DependencyInjection;

use Eghojansu\Bundle\SetupBundle\EghojansuSetupBundle;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(EghojansuSetupBundle::BUNDLE_ID);

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        $rootNode
            ->children()
                ->scalarNode('passphrase')
                    ->cannotBeEmpty()
                    ->defaultValue('admin')
                ->end()
                ->booleanNode('disable')
                    ->defaultFalse()
                ->end()
                ->booleanNode('disable_locale')
                    ->defaultFalse()
                ->end()
                ->scalarNode('maintenance_path')
                    ->cannotBeEmpty()
                    ->defaultValue('/maintenance')
                ->end()
                ->scalarNode('history_path')
                    ->cannotBeEmpty()
                    ->defaultValue('%kernel.project_dir%/var')
                ->end()
                ->arrayNode('versions')
                    ->cannotBeEmpty()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('version')
                                ->isRequired(true)
                            ->end()
                            ->scalarNode('description')
                                ->defaultNull()
                            ->end()
                            ->arrayNode('parameters')
                                ->cannotBeEmpty()
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('key')
                                        ->cannotBeEmpty()
                                        ->defaultValue('parameters')
                                    ->end()
                                    ->scalarNode('destination')
                                        ->cannotBeEmpty()
                                        ->defaultValue('%kernel.project_dir%/app/config/parameters.yml')
                                    ->end()
                                    ->arrayNode('sources')
                                        ->cannotBeEmpty()
                                        ->prototype('scalar')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('config')
                                ->cannotBeEmpty()
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('value')
                                            ->isRequired(true)
                                        ->end()
                                        ->arrayNode('options')
                                            ->cannotBeEmpty()
                                            ->prototype('scalar')
                                            ->end()
                                        ->end()
                                        ->scalarNode('group')
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->scalarNode('description')
                                            ->defaultNull()
                                        ->end()
                                        ->booleanNode('required')
                                            ->defaultFalse()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
