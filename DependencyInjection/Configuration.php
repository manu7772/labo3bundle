<?php

namespace laboBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 * and this : {@link http://stackoverflow.com/questions/4821692/how-do-i-read-configuration-settings-from-symfony2-config-yml}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('labo');

        $rootNode
            ->children()
                ->scalarNode('default_menu')
                    ->defaultValue('menu_01')
                    ->info('Slug du menu par défaut du site')
                ->end()
                ->scalarNode('user_class')
                    ->defaultValue('AcmeGroup\UserBundle\Entity\User')
                    ->info('Classe FOS/User')
                ->end()
            ->end()
        ;
        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
