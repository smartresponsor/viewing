<?php

declare(strict_types=1);

namespace App\Viewing\Resolver;

use App\Viewing\ServiceInterface\ViewStatusCodeResolverInterface;
use App\Viewing\Value\ViewPayload;

/**
 * Resolves StatusCode decisions without transferring presentation ownership outside Viewing.
 */
final readonly class ViewStatusCodeResolver implements ViewStatusCodeResolverInterface
{
    /**
     * Resolves the canonical Viewing value for the supplied payload and request context.
     */
    public function resolve(ViewPayload $payload, ?int $override = null, int $fallback = 200): int
    {
        if (null !== $override && $this->isValid($override)) {
            return $override;
        }

        $statusCode = $payload->meta['status_code'] ?? null;

        if (is_string($statusCode) && ctype_digit($statusCode)) {
            $statusCode = (int) $statusCode;
        }

        return is_int($statusCode) && $this->isValid($statusCode)
            ? $statusCode
            : $fallback;
    }

    private function isValid(int $statusCode): bool
    {
        return $statusCode >= 100 && $statusCode <= 599;
    }
}
