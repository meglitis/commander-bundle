<?php

namespace Guscware\CommanderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    const DEFAULT_AUTO_UNLOCK_AFTER = 300; // After 5 minutes

    /** @var string */
    private $lockfilePath;

    /**
     * Configuration constructor.
     *
     * @param string $lockfilePath
     */
    public function __construct($lockfilePath)
    {
        $this->lockfilePath = $lockfilePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('commander');

        $rootNode
            ->children()
                ->scalarNode('lockfile_directory')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->defaultValue($this->lockfilePath)
                    ->treatNullLike($this->lockfilePath)
                ->end()
                ->scalarNode('auto_unlock_after')
                    ->cannotBeEmpty()
                    ->defaultValue(self::DEFAULT_AUTO_UNLOCK_AFTER)
                    ->treatNullLike(self::DEFAULT_AUTO_UNLOCK_AFTER)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
