<?php

declare(strict_types=1);

namespace App\Viewing\Renderer;

use App\Viewing\Contract\ViewInterfaceLocationComposerInterface;
use App\Viewing\ServiceInterface\ViewObservabilityServiceInterface;
use App\Viewing\ServiceInterface\ViewStatusCodeResolverInterface;
use App\Viewing\ServiceInterface\ViewTemplateRendererInterface;
use App\Viewing\ServiceInterface\ViewTemplateResolverInterface;
use App\Viewing\Value\ViewDecision;
use App\Viewing\Value\ViewPayload;
use App\Viewing\Value\ViewRequestContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Renders the first viable canonical Twig candidate and preserves controlled fallback diagnostics across template failures.
 */
final readonly class ViewTemplateRenderer implements ViewTemplateRendererInterface
{
    public function __construct(
        private Environment $twig,
        private ViewTemplateResolverInterface $templateResolver,
        private RequestStack $requestStack,
        private ?ViewInterfaceLocationComposerInterface $interfaceLocationComposeService = null,
        private ?ViewStatusCodeResolverInterface $statusCodeResolver = null,
        private ?ViewObservabilityServiceInterface $observability = null,
    ) {
    }

    /**
     * Renders the selected template with normalized context while preserving Viewing fallback semantics.
     */
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
        $locations = $this->composedLocations($request, $locations);
        $compositionMs = (microtime(true) - $compositionStartedAt) * 1000;

        foreach ($resolution->availableCandidates as $candidate) {
            try {
                $contextStartedAt = microtime(true);
                $renderContext = $this->renderContext(
                    $payload,
                    $context,
                    $decision,
                    $resolution->toArray(),
                    $locations,
                    $candidate,
                );

                // Viewing keeps its reserved keys authoritative, then exposes
                // producer payload data as template context after the canonical
                // interface.locations projection has been assembled.
                $contextMs = (microtime(true) - $contextStartedAt) * 1000;
                $twigStartedAt = microtime(true);
                $content = $this->twig->render($candidate, $renderContext + $payload->data);
                $twigMs = (microtime(true) - $twigStartedAt) * 1000;
            } catch (\Throwable $exception) {
                $this->recordRenderFailure($exception, $context, $decision, $candidate, $startedAt, $request);

                continue;
            }

            return $this->renderedResponse(
                $content,
                $payload,
                $context,
                $decision,
                $candidate,
                $startedAt,
                $resolutionMs,
                $compositionMs,
                $contextMs,
                $twigMs,
            );
        }

        return null;
    }

    private function renderedResponse(
        string $content,
        ViewPayload $payload,
        ViewRequestContext $context,
        ViewDecision $decision,
        string $candidate,
        float $startedAt,
        float $resolutionMs,
        float $compositionMs,
        float $contextMs,
        float $twigMs,
    ): Response {
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
        $response->headers->set('X-Viewing-Template', $candidate);

        return $response;
    }

    private function recordRenderFailure(
        \Throwable $exception,
        ViewRequestContext $context,
        ViewDecision $decision,
        string $candidate,
        float $startedAt,
        ?\Symfony\Component\HttpFoundation\Request $request,
    ): void {
        $this->observability?->record('template_render_failure', [
            'route' => $context->routeName,
            'path' => $context->path,
            'actor_type' => $context->actorType,
            'exception_class' => $exception::class,
            'candidate_depth' => \count($decision->templateCandidates),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ], 'error');

        if (null === $request) {
            return;
        }

        $failures = $request->attributes->get('_view_render_failures');
        $failures = \is_array($failures) ? $failures : [];
        $failures[] = [
            'template' => $candidate,
            'exception' => $exception::class,
            'message' => mb_substr($exception->getMessage(), 0, 300),
        ];
        $request->attributes->set('_view_render_failures', $failures);
    }

    /**
     * @param array<string, mixed>                      $resolution
     * @param array<string, list<array<string, mixed>>> $locations
     *
     * @return array<string, mixed>
     */
    private function renderContext(
        ViewPayload $payload,
        ViewRequestContext $context,
        ViewDecision $decision,
        array $resolution,
        array $locations,
        string $candidate,
    ): array {
        $payloadArray = $payload->toArray();

        return [
            'view' => $payloadArray['_view'],
            'interface' => ['locations' => $locations],
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
                'template_resolution' => $resolution,
                'decision_reasons' => $decision->reasons,
            ],
        ];
    }

    /**
     * @param array<string, list<array<string, mixed>>> $locations
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function composedLocations(?\Symfony\Component\HttpFoundation\Request $request, array $locations): array
    {
        if (null === $request || null === $this->interfaceLocationComposeService) {
            return $locations;
        }

        return $this->mergeLocations(
            $locations,
            $this->interfaceLocationComposeService->composeLocations($request),
        );
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
