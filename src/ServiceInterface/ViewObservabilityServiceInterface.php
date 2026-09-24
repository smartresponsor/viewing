<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

interface ViewObservabilityServiceInterface
{
    /** @param array<string, scalar|null> $context */
    public function record(string $event, array $context = [], string $level = 'info'): void;
}
