<?php

declare(strict_types=1);

namespace App\Viewing\Factory;

use App\Viewing\ServiceInterface\ViewJsonResponseFactoryInterface;
use App\Viewing\ServiceInterface\ViewJsonSerializerInterface;
use App\Viewing\ServiceInterface\ViewObservabilityServiceInterface;
use App\Viewing\ServiceInterface\ViewStatusCodeResolverInterface;
use App\Viewing\Value\ViewDecision;
use App\Viewing\Value\ViewPayload;
use App\Viewing\Value\ViewRequestContext;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Creates JsonResponse values while keeping construction policy inside the Viewing boundary.
 */
final readonly class ViewJsonResponseFactory implements ViewJsonResponseFactoryInterface
{
    public function __construct(
        private int $fallbackStatusCode = 200,
        private string $diagnosticMode = 'safe',
        private ?ViewStatusCodeResolverInterface $statusCodeResolver = null,
        private ?ViewJsonSerializerInterface $jsonSerializer = null,
        private ?ViewObservabilityServiceInterface $observability = null,
    ) {
    }

    /**
     * Creates the typed Viewing result represented by this factory from normalized request data.
     */
    public function create(ViewPayload $payload, ViewRequestContext $context, ViewDecision $decision): JsonResponse
    {
        $data = $payload->toArray();
        $viewing = [
            'mode' => $decision->mode,
            'reasons' => $decision->reasons,
        ];

        if ('off' !== $this->diagnosticMode) {
            $viewing += [
                'route' => $context->routeName,
                'path' => $context->path,
                'method' => $context->method,
                'request_format' => $context->requestFormat,
                'actor_type' => $context->actorType,
            ];
        }

        if ('debug' === $this->diagnosticMode && [] !== $decision->templateCandidates) {
            $viewing['template_candidates'] = $decision->templateCandidates;
        }

        $data = [
            '_view' => $data['_view'] ?? null,
            '_viewing' => $viewing,
        ] + array_diff_key($data, [
            '_view' => true,
            '_viewing' => true,
        ]);

        try {
            $json = $this->jsonSerializer?->serialize($data)
                ?? json_encode($data, \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_THROW_ON_ERROR, 64);
            $statusCode = $this->statusCodeResolver?->resolve($payload, $decision->statusCodeOverride, $this->fallbackStatusCode)
                ?? $this->fallbackStatusCode;
            $this->observability?->record('json_response', [
                'route' => $context->routeName,
                'actor_type' => $context->actorType,
                'decision_mode' => $decision->mode,
                'status_code' => $statusCode,
            ]);
        } catch (\JsonException $exception) {
            $json = '{"ok":false,"component":"viewing","reason":"serialization_degraded"}';
            $statusCode = 500;
            $this->observability?->record('json_serialization_failure', [
                'route' => $context->routeName,
                'actor_type' => $context->actorType,
                'exception_class' => $exception::class,
            ], 'error');
        }

        return new JsonResponse($json, $statusCode, [], true);
    }
}
