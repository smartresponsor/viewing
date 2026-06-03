<?php

declare(strict_types=1);

use App\Viewing\Service\View\ViewDecisionService;
use App\Viewing\Service\View\ViewJsonResponseFactory;
use App\Viewing\Service\View\ViewPayloadNormalizer;
use App\Viewing\Service\View\ViewRequestContextFactory;
use App\Viewing\Service\View\ViewResponseGuardService;
use App\Viewing\Service\View\ViewRouteExclusionService;
use App\Viewing\Service\View\ViewTemplateCandidateService;
use App\Viewing\Service\View\ViewTemplateRenderer;
use App\Viewing\Service\View\ViewTemplateResolver;
use App\Viewing\Service\View\ViewTrafficClassifier;
use App\Viewing\ServiceInterface\View\ViewDecisionServiceInterface;
use App\Viewing\ServiceInterface\View\ViewJsonResponseFactoryInterface;
use App\Viewing\ServiceInterface\View\ViewPayloadNormalizerInterface;
use App\Viewing\ServiceInterface\View\ViewRequestContextFactoryInterface;
use App\Viewing\ServiceInterface\View\ViewResponseGuardServiceInterface;
use App\Viewing\ServiceInterface\View\ViewRouteExclusionServiceInterface;
use App\Viewing\ServiceInterface\View\ViewTemplateCandidateServiceInterface;
use App\Viewing\ServiceInterface\View\ViewTemplateRendererInterface;
use App\Viewing\ServiceInterface\View\ViewTemplateResolverInterface;
use App\Viewing\ServiceInterface\View\ViewTrafficClassifierInterface;
use App\Viewing\Subscriber\View\ViewKernelResponseGuardSubscriber;
use App\Viewing\Subscriber\View\ViewKernelViewSubscriber;
use App\Viewing\Subscriber\View\ViewTrafficRequestSubscriber;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('App\\Viewing\\', '../../src/')
        ->exclude('../../src/{DependencyInjection,Value,ViewingBundle.php,Kernel.php}');

    $services->alias(ViewPayloadNormalizerInterface::class, ViewPayloadNormalizer::class);
    $services->alias(ViewRequestContextFactoryInterface::class, ViewRequestContextFactory::class);
    $services->alias(ViewDecisionServiceInterface::class, ViewDecisionService::class);
    $services->alias(ViewTemplateCandidateServiceInterface::class, ViewTemplateCandidateService::class);
    $services->alias(ViewTemplateResolverInterface::class, ViewTemplateResolver::class);
    $services->alias(ViewTemplateRendererInterface::class, ViewTemplateRenderer::class);
    $services->alias(ViewJsonResponseFactoryInterface::class, ViewJsonResponseFactory::class);
    $services->alias(ViewResponseGuardServiceInterface::class, ViewResponseGuardService::class);
    $services->alias(ViewRouteExclusionServiceInterface::class, ViewRouteExclusionService::class);
    $services->alias(ViewTrafficClassifierInterface::class, ViewTrafficClassifier::class);

    $services->set(ViewKernelViewSubscriber::class)
        ->arg('$enabled', '%viewing.enabled%');

    $services->set(ViewKernelResponseGuardSubscriber::class)
        ->arg('$routeExclusionService', service(ViewRouteExclusionServiceInterface::class));

    $services->set(ViewTrafficRequestSubscriber::class)
        ->arg('$actorRequestAttribute', '%viewing.actor_request_attribute%')
        ->arg('$enabled', '%viewing.traffic_classifier_enabled%');

    $services->set(ViewRequestContextFactory::class)
        ->arg('$actorRequestAttribute', '%viewing.actor_request_attribute%');

    $services->set(ViewDecisionService::class)
        ->arg('$botActorValues', '%viewing.bot_actor_values%');

    $services->set(ViewTemplateCandidateService::class)
        ->arg('$interfacingTwigNamespace', '%viewing.interfacing_twig_namespace%')
        ->arg('$viewingTwigNamespace', '%viewing.viewing_twig_namespace%')
        ->arg('$localComponentFallbackEnabled', '%viewing.local_component_fallback_enabled%')
        ->arg('$diagnosticMode', '%viewing.diagnostic_mode%');

    $services->set(ViewTemplateRenderer::class)
        ->arg('$templateResolver', service(ViewTemplateResolverInterface::class));

    $services->set(ViewJsonResponseFactory::class)
        ->arg('$fallbackStatusCode', '%viewing.json_fallback_status_code%')
        ->arg('$diagnosticMode', '%viewing.diagnostic_mode%');

    $services->set(ViewResponseGuardService::class)
        ->arg('$controlledRouteAttribute', '%viewing.controlled_route_attribute%')
        ->arg('$enabled', '%viewing.response_guard_enabled%')
        ->arg('$debug', '%viewing.debug_response_guard%');

    $services->set(ViewRouteExclusionService::class)
        ->arg('$excludedPathPatterns', '%viewing.excluded_path_patterns%')
        ->arg('$excludedRoutePatterns', '%viewing.excluded_route_patterns%');

    $services->set(ViewTrafficClassifier::class)
        ->arg('$botUserAgentPatterns', '%viewing.bot_user_agent_patterns%');
};
