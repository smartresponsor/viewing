<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the public ViewRouteExclusionService contract used across the Viewing presentation boundary.
 */
interface ViewRouteExclusionServiceInterface
{
    public function isExcluded(Request $request): bool;
}
