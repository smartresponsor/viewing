<?php

declare(strict_types=1);

namespace App\Viewing\Service\View;

use App\Viewing\ServiceInterface\View\ViewJsonResponseFactoryInterface;
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
    ) {
    }

    public function create(ViewPayload $payload, ViewRequestContext $context, ViewDecision $decision): JsonResponse
    {
        $data = $payload->toArray();
        $data['_viewing'] = [
            'mode' => $decision->mode,
            'reasons' => $decision->reasons,
        ];

        if ('off' !== $this->diagnosticMode) {
            $data['_viewing'] += [
                'route' => $context->routeName,
                'path' => $context->path,
                'method' => $context->method,
                'request_format' => $context->requestFormat,
                'actor_type' => $context->actorType,
            ];
        }

        if ('debug' === $this->diagnosticMode && [] !== $decision->templateCandidates) {
            $data['_viewing']['template_candidates'] = $decision->templateCandidates;
        }

        $json = json_encode($data, self::JSON_FLAGS);
        if (false === $json) {
            $json = '{"ok":false,"component":"viewing","reason":"json_encode_failed"}';
        }

        return new JsonResponse($json, $this->statusCode($payload), [], true);
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

        return $this->fallbackStatusCode;
    }
}
