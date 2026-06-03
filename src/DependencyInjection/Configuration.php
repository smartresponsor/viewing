<?php

declare(strict_types=1);

namespace App\Viewing\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('viewing');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('enabled')->defaultTrue()->end()
                ->arrayNode('bot_actor_values')
                    ->scalarPrototype()->end()
                    ->defaultValue(['bot'])
                ->end()
                ->scalarNode('actor_request_attribute')->defaultValue('_view_actor_type')->end()
                ->scalarNode('controlled_route_attribute')->defaultValue('_view_controlled')->end()
                ->booleanNode('response_guard_enabled')->defaultTrue()->end()
                ->booleanNode('debug_response_guard')->defaultFalse()->end()
                ->enumNode('diagnostic_mode')
                    ->values(['off', 'safe', 'debug'])
                    ->defaultValue('safe')
                ->end()
                ->booleanNode('traffic_classifier_enabled')->defaultTrue()->end()
                ->arrayNode('bot_user_agent_patterns')
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        '/bot/i',
                        '/crawl/i',
                        '/spider/i',
                        '/slurp/i',
                        '/headless/i',
                        '/python-requests/i',
                        '/curl/i',
                        '/wget/i',
                    ])
                ->end()
                ->arrayNode('excluded_path_patterns')
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        '#^/_wdt(?:/|$)#',
                        '#^/_profiler(?:/|$)#',
                        '#^/assets(?:/|$)#',
                        '#^/build(?:/|$)#',
                        '#^/favicon\\.ico$#',
                        '#^/robots\\.txt$#',
                        '#^/sitemap\\.xml$#',
                        '#^/health$#',
                        '#^/metrics$#',
                    ])
                ->end()
                ->arrayNode('excluded_route_patterns')
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        '#^_profiler#',
                        '#^_wdt#',
                        '#health$#',
                        '#metrics$#',
                    ])
                ->end()
                ->scalarNode('interfacing_twig_namespace')->defaultValue('Interfacing')->end()
                ->scalarNode('viewing_twig_namespace')->defaultValue('Viewing')->end()
                ->booleanNode('local_component_fallback_enabled')->defaultTrue()->end()
                ->integerNode('json_fallback_status_code')->defaultValue(200)->end()
            ->end();

        return $treeBuilder;
    }
}
