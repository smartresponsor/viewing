<?php

declare(strict_types=1);

namespace App\Viewing\Service\View;

use App\ServiceInterface\InterfaceLocation\AppInterfaceLocationComposeServiceInterface;
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
    ) {
    }

    public function render(ViewPayload $payload, ViewRequestContext $context, ViewDecision $decision): ?Response
    {
        $resolution = $this->templateResolver->resolve($decision->templateCandidates);
        $locations = $payload->locations;
        $request = $this->requestStack->getCurrentRequest();

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
            } catch (\Throwable) {
                continue;
            }

            return new Response($content, $this->statusCode($payload), ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        return null;
    }

    private function statusCode(ViewPayload $payload): int
    {
        $statusCode = $payload->meta['status_code'] ?? null;

        if (is_int($statusCode) && $statusCode >= 100 && $statusCode <= 599) {
            return $statusCode;
        }

        if (is_string($statusCode) && ctype_digit($statusCode)) {
            $statusCode = (int) $statusCode;

            if ($statusCode >= 100 && $statusCode <= 599) {
                return $statusCode;
            }
        }

        return Response::HTTP_OK;
    }

    /**
     * @param array<string, list<array<string, mixed>>> $left
     * @param array<string, list<array<string, mixed>>> $right
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function mergeLocations(array $left, array $right): array
    {
        foreach ($right as $location => $blocks) {
            $left[$location] = array_values(array_merge($left[$location] ?? [], $blocks));
        }

        return $left;
    }
}
