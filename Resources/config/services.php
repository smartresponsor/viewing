<?php

declare(strict_types=1);

use App\Viewing\Service\ViewDecisionService;
use App\Viewing\Factory\ViewJsonResponseFactory;
use App\Viewing\Service\ViewJsonSerializer;
use App\Viewing\Service\ViewObservabilityService;
use App\Viewing\Normalizer\ViewPayloadNormalizer;
use App\Viewing\Factory\ViewRequestContextFactory;
use App\Viewing\Service\ViewResponseGuardService;
use App\Viewing\Service\ViewRouteExclusionService;
use App\Viewing\Service\ViewTemplateCandidateService;
use App\Viewing\Renderer\ViewTemplateRenderer;
use App\Viewing\Normalizer\ViewObjectPayloadNormalizer;
use App\Viewing\Resolver\ViewStatusCodeResolver;
use App\Viewing\Resolver\ViewTemplateResolver;
use App\Viewing\Service\ViewTrafficClassifier;
use App\Viewing\ServiceInterface\ViewDecisionServiceInterface;
use App\Viewing\Contract\ViewInterfaceLocationComposerInterface;
use App\Viewing\ServiceInterface\ViewJsonResponseFactoryInterface;
use App\Viewing\ServiceInterface\ViewJsonSerializerInterface;
use App\Viewing\ServiceInterface\ViewObservabilityServiceInterface;
use App\Viewing\ServiceInterface\ViewPayloadNormalizerInterface;
use App\Viewing\ServiceInterface\ViewRequestContextFactoryInterface;
use App\Viewing\ServiceInterface\ViewResponseGuardServiceInterface;
use App\Viewing\ServiceInterface\ViewRouteExclusionServiceInterface;
use App\Viewing\ServiceInterface\ViewTemplateCandidateServiceInterface;
use App\Viewing\ServiceInterface\ViewObjectPayloadNormalizerInterface;
use App\Viewing\ServiceInterface\ViewStatusCodeResolverInterface;
use App\Viewing\ServiceInterface\ViewTemplateRendererInterface;
use App\Viewing\ServiceInterface\ViewTemplateResolverInterface;
use App\Viewing\ServiceInterface\ViewTrafficClassifierInterface;
use App\Viewing\EventSubscriber\ViewKernelResponseGuardSubscriber;
use App\Viewing\EventSubscriber\ViewKernelViewSubscriber;
use App\Viewing\EventSubscriber\ViewTrafficRequestSubscriber;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('App\\Viewing\\', '../../src/')
        ->exclude('../../src/{DependencyInjection,Value,ViewingBundle.php,Kernel.php}');

    $services->alias(ViewJsonSerializerInterface::class, ViewJsonSerializer::class);
    $services->alias(ViewObservabilityServiceInterface::class, ViewObservabilityService::class);
    $services->alias(ViewPayloadNormalizerInterface::class, ViewPayloadNormalizer::class);
    $services->alias(ViewObjectPayloadNormalizerInterface::class, ViewObjectPayloadNormalizer::class);
    $services->alias(ViewRequestContextFactoryInterface::class, ViewRequestContextFactory::class);
    $services->alias(ViewDecisionServiceInterface::class, ViewDecisionService::class);
    $services->alias(ViewTemplateCandidateServiceInterface::class, ViewTemplateCandidateService::class);
    $services->alias(ViewTemplateResolverInterface::class, ViewTemplateResolver::class);
    $services->alias(ViewStatusCodeResolverInterface::class, ViewStatusCodeResolver::class);
    $services->alias(ViewTemplateRendererInterface::class, ViewTemplateRenderer::class);
    $services->alias(ViewJsonResponseFactoryInterface::class, ViewJsonResponseFactory::class);
    $services->alias(ViewResponseGuardServiceInterface::class, ViewResponseGuardService::class);
    $services->alias(ViewRouteExclusionServiceInterface::class, ViewRouteExclusionService::class);
    $services->alias(ViewTrafficClassifierInterface::class, ViewTrafficClassifier::class);

    $services->set(ViewObservabilityService::class)
        ->arg('$logger', service(LoggerInterface::class)->nullOnInvalid());

    $services->set(ViewKernelViewSubscriber::class)
        ->arg('$enabled', '%viewing.enabled%')
        ->arg('$observability', service(ViewObservabilityServiceInterface::class));

    $services->set(ViewKernelResponseGuardSubscriber::class)
        ->arg('$routeExclusionService', service(ViewRouteExclusionServiceInterface::class))
        ->arg('$observability', service(ViewObservabilityServiceInterface::class));

    $services->set(ViewTrafficRequestSubscriber::class)
        ->arg('$actorRequestAttribute', '%viewing.actor_request_attribute%')
        ->arg('$enabled', '%viewing.traffic_classifier_enabled%');

    $services->set(ViewRequestContextFactory::class)
        ->arg('$actorRequestAttribute', '%viewing.actor_request_attribute%');

    $services->set(ViewDecisionService::class)
        ->arg('$botActorValues', '%viewing.bot_actor_values%')
        ->arg('$unknownActorPolicy', '%viewing.unknown_actor_policy%');

    $services->set(ViewTemplateCandidateService::class)
        ->arg('$interfacingTwigNamespace', '%viewing.interfacing_twig_namespace%')
        ->arg('$viewingTwigNamespace', '%viewing.viewing_twig_namespace%')
        ->arg('$localComponentFallbackEnabled', '%viewing.local_component_fallback_enabled%')
        ->arg('$diagnosticMode', '%viewing.diagnostic_mode%');

    $services->set(ViewTemplateRenderer::class)
        ->arg('$templateResolver', service(ViewTemplateResolverInterface::class))
        ->arg('$interfaceLocationComposeService', service(ViewInterfaceLocationComposerInterface::class)->nullOnInvalid())
        ->arg('$statusCodeResolver', service(ViewStatusCodeResolverInterface::class))
        ->arg('$observability', service(ViewObservabilityServiceInterface::class));

    $services->set(ViewJsonResponseFactory::class)
        ->arg('$fallbackStatusCode', '%viewing.json_fallback_status_code%')
        ->arg('$diagnosticMode', '%viewing.diagnostic_mode%')
        ->arg('$statusCodeResolver', service(ViewStatusCodeResolverInterface::class))
        ->arg('$jsonSerializer', service(ViewJsonSerializerInterface::class))
        ->arg('$observability', service(ViewObservabilityServiceInterface::class));

    $services->set(ViewResponseGuardService::class)
        ->arg('$controlledRouteAttribute', '%viewing.controlled_route_attribute%')
        ->arg('$guardMode', '%viewing.response_guard_mode%')
        ->arg('$debug', '%viewing.debug_response_guard%');

    $services->set(ViewRouteExclusionService::class)
        ->arg('$excludedPathPatterns', '%viewing.excluded_path_patterns%')
        ->arg('$excludedRoutePatterns', '%viewing.excluded_route_patterns%');

    $services->set(ViewTrafficClassifier::class)
        ->arg('$botUserAgentPatterns', '%viewing.bot_user_agent_patterns%');
};
