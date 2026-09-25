<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewPayload;

/**
 * Defines the public ViewStatusCodeResolver contract used across the Viewing presentation boundary.
 */
interface ViewStatusCodeResolverInterface
{
    /**
     * Resolves the canonical Viewing value for the supplied payload and request context.
     */
    public function resolve(ViewPayload $payload, ?int $override = null, int $fallback = 200): int;
}
