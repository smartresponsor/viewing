<?php

declare(strict_types=1);

namespace App\Viewing\Service;

use App\Viewing\ServiceInterface\ViewRouteExclusionServiceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Owns the RouteExclusion service responsibility inside the Viewing presentation boundary.
 */
final readonly class ViewRouteExclusionService implements ViewRouteExclusionServiceInterface
{
    /**
     * @param list<string> $excludedPathPatterns
     * @param list<string> $excludedRoutePatterns
     */
    public function __construct(
        private array $excludedPathPatterns = [],
        private array $excludedRoutePatterns = [],
    ) {
    }

    public function isExcluded(Request $request): bool
    {
        $path = $request->getPathInfo();
        $route = $request->attributes->get('_route');

        if ($this->matchesAny($path, $this->excludedPathPatterns)) {
            return true;
        }

        return \is_string($route) && $this->matchesAny($route, $this->excludedRoutePatterns);
    }

    /**
     * @param list<string> $patterns
     */
    private function matchesAny(string $value, array $patterns): bool
    {
        return array_any(
            $patterns,
            static fn (mixed $pattern): bool => \is_string($pattern)
                && '' !== trim($pattern)
                && 1 === @preg_match(trim($pattern), $value),
        );
    }
}
