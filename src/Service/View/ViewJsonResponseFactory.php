<?php

declare(strict_types=1);

namespace App\Viewing\Service\View;

use App\Viewing\ServiceInterface\View\ViewJsonResponseFactoryInterface;
use App\Viewing\ServiceInterface\View\ViewStatusCodeResolverInterface;
use App\Viewing\Value\View\ViewDecision;
use App\Viewing\Value\View\ViewPayload;
use App\Viewing\Value\View\ViewRequestContext;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class ViewJsonResponseFactory implements ViewJsonResponseFactoryInterface
{
    private const JSON_FLAGS = \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_PARTIAL_OUTPUT_ON_ERROR;

    public function __construct(
        private int $fallbackStatusCode = 200,
        private string $diagnosticMode = 'safe',
        private ?ViewStatusCodeResolverInterface $statusCodeResolver = null,
    ) {
    }

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

        $json = json_encode($data, self::JSON_FLAGS);
        if (false === $json) {
            $json = '{"ok":false,"component":"viewing","reason":"json_encode_failed"}';
        }

        $statusCode = $this->statusCodeResolver?->resolve($payload, $decision->statusCodeOverride, $this->fallbackStatusCode)
            ?? $this->fallbackStatusCode;

        return new JsonResponse($json, $statusCode, [], true);
    }
}
