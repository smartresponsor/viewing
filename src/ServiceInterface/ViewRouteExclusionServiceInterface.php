<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use Symfony\Component\HttpFoundation\Request;

interface ViewRouteExclusionServiceInterface
{
    public function isExcluded(Request $request): bool;
}
