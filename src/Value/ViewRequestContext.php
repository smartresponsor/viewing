<?php

declare(strict_types=1);

namespace App\Viewing\Value;

/**
 * Represents the RequestContext immutable value used by the Viewing presentation decision pipeline.
 */
final readonly class ViewRequestContext
{
    /**
     * @param array<string, mixed> $routeAttributes
     */
    public function __construct(
        public string $path,
        public string $method,
        public ?string $routeName = null,
        public string $requestFormat = 'html',
        public ?string $actorType = null,
        public bool $prefersHtml = true,
        public bool $prefersJson = false,
        public bool $xmlHttpRequest = false,
        public array $routeAttributes = [],
    ) {
    }
}
