<?php

namespace laboBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class LaboExtension extends Extension {

	const LABO_NAME = 'labo';

	/**
	 * {@inheritDoc}
	 */
	public function load(array $configs, ContainerBuilder $container) {
		$configuration = new Configuration();
		$config = $this->processConfiguration($configuration, $configs);

		$loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		$loader->load('services.yml');

		/** Ajoute les données de config pour les services aeTools
		 * @link http://stackoverflow.com/questions/4821692/how-do-i-read-configuration-settings-from-symfony2-config-yml
		 */
		$container->getDefinition('labobundle.aetools')->addMethodCall('setConfig', array(self::LABO_NAME => $config));
		$container->getDefinition('labobundle.entities')->addMethodCall('setConfig', array(self::LABO_NAME => $config));
		// $container->getDefinition('labobundle.version')->addMethodCall('setConfig', array(self::LABO_NAME => $config));
	}
}
