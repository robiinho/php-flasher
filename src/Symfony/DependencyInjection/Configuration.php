<?php

namespace Flasher\Symfony\DependencyInjection;

use Flasher\Symfony\Bridge\Bridge;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('flasher');

        if (\method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('flasher');
        }

        $rootNode
            ->children()
                ->scalarNode('default')
                    ->cannotBeEmpty()
                    ->defaultValue('template')
                ->end()
                ->scalarNode('root_script')
                    ->defaultValue('https://cdn.jsdelivr.net/npm/@flasher/flasher@0.7.1/dist/flasher.min.js')
                ->end()
                ->arrayNode('root_scripts')
                    ->prototype('scalar')->end()
                    ->defaultValue(array())
                ->end()
                ->arrayNode('template_factory')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('tailwindcss')
                        ->end()
                        ->arrayNode('templates')
                            ->ignoreExtraKeys()
                            ->prototype('variable')->end()
                            ->children()
                                ->scalarNode('view')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->arrayNode('styles')->end()
                                ->arrayNode('scripts')->end()
                                ->arrayNode('options')->end()
                            ->end()
                            ->defaultValue(array(
                                'tailwindcss' => array(
                                    'view' => Configuration::getTemplate('tailwindcss.html.twig'),
                                    'styles' => array(
                                        'https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.11/base.min.css',
                                        'https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.11/utilities.css',
                                    ),
                                ),
                                'tailwindcss_bg' => array(
                                    'view' => Configuration::getTemplate('tailwindcss_bg.html.twig'),
                                    'styles' => array(
                                        'https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.11/base.min.css',
                                        'https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.11/utilities.css',
                                    ),
                                ),
                                'bootstrap' => array(
                                    'view' => Configuration::getTemplate('bootstrap.html.twig'),
                                    'styles' => array(
                                        'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css',
                                    ),
                                ),
                            ))
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('auto_create_from_session')
                    ->defaultValue(true)
                ->end()
                ->arrayNode('types_mapping')
                    ->prototype('variable')->end()
                    ->defaultValue(array(
                        'success' => array('success'),
                        'error' => array('error', 'danger'),
                        'warning' => array('warning', 'alarm'),
                        'info' => array('info', 'notice', 'alert'),
                    ))
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * @param $template string
     *
     * @return string
     */
    public static function getTemplate($template)
    {
        return Bridge::versionCompare('2.2', '<')
            ? 'FlasherSymfonyBundle::' . $template
            : '@FlasherSymfony/' . $template;
    }
}
