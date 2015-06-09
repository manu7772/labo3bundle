<?php

namespace laboBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 * and this : {@link http://stackoverflow.com/questions/4821692/how-do-i-read-configuration-settings-from-symfony2-config-yml}
 * Class ArrayNodeDefinition : {@link http://api.symfony.com/2.6/Symfony/Component/Config/Definition/Builder/ArrayNodeDefinition.html}
 */
class Configuration implements ConfigurationInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root('labo'); // returns class ArrayNodeDefinition

		$menuInvalids = array("default_menu", "menu_01b", "menu_02", "menu_03");

		$rootNode
			->children()
				->scalarNode('default_menu_slug')
					->defaultValue('default_menu')
					->validate()
						->ifInArray($menuInvalids)
						->thenInvalid('Le slug de menu %s n\'est pas valide, il ne peut pas être '.json_encode($menuInvalids))
					->end()
					->isRequired()
					->cannotBeEmpty()
					->info('Slug du menu par défaut du site')
				->end()
				->scalarNode('user_class')
					->defaultValue('Acme\UserBundle\Entity\User')
					->isRequired()
					->cannotBeEmpty()
					->info('Classe pour FOS/User')
				->end()
				->booleanNode('entity_listener')
					->defaultTrue()
					->info('Activation du listener')
					// ->canBeUnset()
				->end()
				->arrayNode('launch_service')
					->children()
						->booleanNode('activate')
							->defaultTrue()
							// ->canBeUnset()
						->end()
					->end()
					->children()
						->arrayNode('resources')
							->canBeUnset()
							->children()
								->arrayNode('services')
									->useAttributeAsKey('name')
									->prototype('array')
										->children()
											->scalarNode('name')->end()
										->end()
									->end()
								->end()
							->end()
						->end()
					->end()
					->info('Services à lancer avec l\'event_listener')
				->end()
			->end()
		;
		// Here you should define the parameters that are allowed to
		// configure your bundle. See the documentation linked above for
		// more information on that topic.

		return $treeBuilder;
	}
}
