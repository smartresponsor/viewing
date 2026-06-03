<?php

declare(strict_types=1);

namespace App\Viewing\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class ViewingExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!$container->hasExtension('twig')) {
            return;
        }

        $container->prependExtensionConfig('twig', [
            'paths' => [
                \dirname(__DIR__, 2).'/templates' => $config['viewing_twig_namespace'],
            ],
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('viewing.enabled', $config['enabled']);
        $container->setParameter('viewing.bot_actor_values', $config['bot_actor_values']);
        $container->setParameter('viewing.actor_request_attribute', $config['actor_request_attribute']);
        $container->setParameter('viewing.controlled_route_attribute', $config['controlled_route_attribute']);
        $container->setParameter('viewing.response_guard_enabled', $config['response_guard_enabled']);
        $container->setParameter('viewing.debug_response_guard', $config['debug_response_guard']);
        $container->setParameter('viewing.diagnostic_mode', $config['diagnostic_mode']);
        $container->setParameter('viewing.traffic_classifier_enabled', $config['traffic_classifier_enabled']);
        $container->setParameter('viewing.bot_user_agent_patterns', $config['bot_user_agent_patterns']);
        $container->setParameter('viewing.excluded_path_patterns', $config['excluded_path_patterns']);
        $container->setParameter('viewing.excluded_route_patterns', $config['excluded_route_patterns']);
        $container->setParameter('viewing.interfacing_twig_namespace', $config['interfacing_twig_namespace']);
        $container->setParameter('viewing.viewing_twig_namespace', $config['viewing_twig_namespace']);
        $container->setParameter('viewing.local_component_fallback_enabled', $config['local_component_fallback_enabled']);
        $container->setParameter('viewing.json_fallback_status_code', $config['json_fallback_status_code']);

        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/Resources/config'));
        $loader->load('services.php');
    }
}
