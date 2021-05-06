<?php

namespace Leankoala\LeanApiBundle\DependencyInjection;

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
        if (method_exists(TreeBuilder::class, 'root')) {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('leankoala_lean_api');
        } else {
            $treeBuilder = new TreeBuilder('leankoala_lean_api');
        }

        return $treeBuilder;
    }
}
