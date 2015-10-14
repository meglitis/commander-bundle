<?php

namespace Guscware\CommanderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class CommanderExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $lockfilePath = $container->getParameter('kernel.root_dir') . '/lockfiles';
        $configuration = new Configuration($lockfilePath);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('commander.lockfile_directory', $config['lockfile_directory']);
        $container->setParameter('commander.auto_unlock_after', $config['auto_unlock_after']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
