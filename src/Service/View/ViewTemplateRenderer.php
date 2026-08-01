<?php

declare(strict_types=1);

namespace App\Viewing\Service\View;

use App\Viewing\ServiceInterface\View\ViewInterfaceLocationComposeServiceInterface;
use App\Viewing\ServiceInterface\View\ViewObservabilityServiceInterface;
use App\Viewing\ServiceInterface\View\ViewStatusCodeResolverInterface;
use App\Viewing\ServiceInterface\View\ViewTemplateRendererInterface;
use App\Viewing\ServiceInterface\View\ViewTemplateResolverInterface;
use App\Viewing\Value\View\ViewDecision;
use App\Viewing\Value\View\ViewPayload;
use App\Viewing\Value\View\ViewRequestContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final readonly class ViewTemplateRenderer implements ViewTemplateRendererInterface
{
    public function __construct(
        private Environment $twig,
        private ViewTemplateResolverInterface $templateResolver,
        private RequestStack $requestStack,
        private ?ViewInterfaceLocationComposeServiceInterface $interfaceLocationComposeService = null,
        private ?ViewStatusCodeResolverInterface $statusCodeResolver = null,
        private ?ViewObservabilityServiceInterface $observability = null,
    ) {
    }

    public function render(ViewPayload $payload, ViewRequestContext $context, ViewDecision $decision): ?Response
    {
        $startedAt = microtime(true);
        $resolutionStartedAt = microtime(true);
        $resolution = $this->templateResolver->resolve($decision->templateCandidates);
        $resolutionMs = (microtime(true) - $resolutionStartedAt) * 1000;
        $locations = $payload->locations;
        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request && [] !== $resolution->loaderFailures) {
            $request->attributes->set('_view_loader_failures', $resolution->loaderFailures);
        }

        $compositionStartedAt = microtime(true);
        if (null !== $request && null !== $this->interfaceLocationComposeService) {
            $locations = $this->mergeLocations(
                $locations,
                $this->interfaceLocationComposeService->composeLocations($request),
            );
        }
        $compositionMs = (microtime(true) - $compositionStartedAt) * 1000;

        foreach ($resolution->availableCandidates as $candidate) {
            try {
                $contextStartedAt = microtime(true);
                $payloadArray = $payload->toArray();
                $renderContext = [
                    'view' => $payloadArray['_view'],
                    'interface' => [
                        'locations' => $locations,
                    ],
                    'locations' => $locations,
                    'data' => $payload->data,
                    'meta' => $payload->meta,
                    'debug' => $payload->debug,
                    'payload' => $payloadArray,
                    'surface' => $payload->surface,
                    'operation' => $payload->operation,
                    'component' => $payload->component,
                    'request_context' => [
                        'path' => $context->path,
                        'method' => $context->method,
                        'route' => $context->routeName,
                        'format' => $context->requestFormat,
                        'actor_type' => $context->actorType,
                    ],
                    'viewing' => [
                        'selected_template' => $candidate,
                        'template_candidates' => $decision->templateCandidates,
                        'template_resolution' => $resolution->toArray(),
                        'decision_reasons' => $decision->reasons,
                    ],
                ];

                // Viewing keeps its reserved keys authoritative, then exposes
                // producer payload data as template context after the canonical
                // interface.locations projection has been assembled.
                $contextMs = (microtime(true) - $contextStartedAt) * 1000;
                $twigStartedAt = microtime(true);
                $content = $this->twig->render($candidate, $renderContext + $payload->data);
                $twigMs = (microtime(true) - $twigStartedAt) * 1000;
            } catch (\Throwable $exception) {
                $this->observability?->record('template_render_failure', [
                    'route' => $context->routeName,
                    'path' => $context->path,
                    'actor_type' => $context->actorType,
                    'exception_class' => $exception::class,
                    'candidate_depth' => \count($decision->templateCandidates),
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ], 'error');

                if (null !== $request) {
                    $failures = $request->attributes->get('_view_render_failures');
                    $failures = \is_array($failures) ? $failures : [];
                    $failures[] = [
                        'template' => $candidate,
                        'exception' => $exception::class,
                        'message' => mb_substr($exception->getMessage(), 0, 300),
                    ];
                    $request->attributes->set('_view_render_failures', $failures);
                }

                continue;
            }

            $statusCode = $this->statusCodeResolver?->resolve($payload) ?? Response::HTTP_OK;
            $this->observability?->record('template_render', [
                'route' => $context->routeName,
                'path' => $context->path,
                'actor_type' => $context->actorType,
                'status_code' => $statusCode,
                'candidate_depth' => \count($decision->templateCandidates),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            $response = new Response($content, $statusCode, ['Content-Type' => 'text/html; charset=UTF-8']);
            $response->headers->set('X-Viewing-Resolve-ms', number_format($resolutionMs, 2, '.', ''));
            $response->headers->set('X-Viewing-Compose-ms', number_format($compositionMs, 2, '.', ''));
            $response->headers->set('X-Viewing-Context-ms', number_format($contextMs, 2, '.', ''));
            $response->headers->set('X-Viewing-Twig-ms', number_format($twigMs, 2, '.', ''));
            if (null !== $request) {
                $response->headers->set('X-App-Crud-Contract-ms', (string) $request->attributes->get('_app_crud_contract_ms', ''));
                $response->headers->set('X-App-Crud-Navigation-ms', (string) $request->attributes->get('_app_crud_navigation_ms', ''));
                $response->headers->set('X-Crud-Context-ms', (string) $request->attributes->get('_crud_context_ms', ''));
                $response->headers->set('X-Crud-Entrypoint-ms', (string) $request->attributes->get('_crud_entrypoint_ms', ''));
                $response->headers->set('X-Crud-Definition-ms', (string) $request->attributes->get('_crud_definition_ms', ''));
                $response->headers->set('X-Crud-Contract-Factory-ms', (string) $request->attributes->get('_crud_contract_factory_ms', ''));
                $response->headers->set('X-Crud-Service-Resolution-ms', (string) $request->attributes->get('_crud_service_resolution_ms', ''));
                $response->headers->set('X-Crud-Service-Invocation-ms', (string) $request->attributes->get('_crud_service_invocation_ms', ''));
            }

            return $response;
        }

        return null;
    }

    /**
     * App-composed locations are authoritative for every location they publish.
     * Producer locations remain available only where App has no projection.
     *
     * @param array<string, list<array<string, mixed>>> $left
     * @param array<string, list<array<string, mixed>>> $right
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function mergeLocations(array $left, array $right): array
    {
        foreach ($right as $location => $blocks) {
            $left[$location] = array_values($blocks);
        }

        return $left;
    }
}
