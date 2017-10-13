<?php

namespace Eghojansu\Bundle\SetupBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;
use Eghojansu\Bundle\SetupBundle\EghojansuSetupBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class EghojansuSetupExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if ($config['versions']) {
            usort($config['versions'], function($a, $b) {
                return version_compare($a['version'], $b['version']);
            });
        }

        $container->setParameter(EghojansuSetupBundle::BUNDLE_ID, $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $env = $container->getParameter('kernel.environment');
        if (in_array($env, ['test'])) {
            $loader->load('services_'.$env.'.yml');
        } else {
            $loader->load('services.yml');
        }
    }
}
