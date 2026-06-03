<?php

declare(strict_types=1);

namespace App\Viewing\Service\View;

use App\Viewing\ServiceInterface\View\ViewRouteExclusionServiceInterface;
use Symfony\Component\HttpFoundation\Request;

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
        foreach ($patterns as $pattern) {
            if (!\is_string($pattern) || '' === trim($pattern)) {
                continue;
            }

            $pattern = trim($pattern);
            $result = @preg_match($pattern, $value);

            if (1 === $result) {
                return true;
            }
        }

        return false;
    }
}
