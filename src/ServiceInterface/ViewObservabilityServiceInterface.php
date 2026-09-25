<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

/**
 * Defines the public ViewObservabilityService contract used across the Viewing presentation boundary.
 */
interface ViewObservabilityServiceInterface
{
    /**
     * Records a structured Viewing observability event without exposing payload or filesystem details.
     *
     * @param array<string, scalar|null> $context
     */
    public function record(string $event, array $context = [], string $level = 'info'): void;
}
