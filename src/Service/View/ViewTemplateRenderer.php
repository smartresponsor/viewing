<?php

declare(strict_types=1);

namespace App\Viewing\Service\View;

use App\ServiceInterface\InterfaceLocation\AppInterfaceLocationComposeServiceInterface;
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
        private ?AppInterfaceLocationComposeServiceInterface $interfaceLocationComposeService = null,
        private ?ViewStatusCodeResolverInterface $statusCodeResolver = null,
    ) {
    }

    public function render(ViewPayload $payload, ViewRequestContext $context, ViewDecision $decision): ?Response
    {
        $resolution = $this->templateResolver->resolve($decision->templateCandidates);
        $locations = $payload->locations;
        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request && [] !== $resolution->loaderFailures) {
            $request->attributes->set('_view_loader_failures', $resolution->loaderFailures);
        }

        if (null !== $request && null !== $this->interfaceLocationComposeService) {
            $locations = $this->mergeLocations(
                $locations,
                $this->interfaceLocationComposeService->composeLocations($request),
            );
        }

        foreach ($resolution->availableCandidates as $candidate) {
            try {
                $renderContext = [
                    'view' => $payload->toArray()['_view'],
                    'interface' => [
                        'locations' => $locations,
                    ],
                    'locations' => $locations,
                    'data' => $payload->data,
                    'meta' => $payload->meta,
                    'debug' => $payload->debug,
                    'payload' => $payload->toArray(),
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
                $content = $this->twig->render($candidate, $renderContext + $payload->data);
            } catch (\Throwable $exception) {
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

            return new Response($content, $statusCode, ['Content-Type' => 'text/html; charset=UTF-8']);
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
