<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface\View;

use Symfony\Component\HttpFoundation\Request;

interface ViewInterfaceLocationComposeServiceInterface
{
    /** @return array<string, list<array<string, mixed>>> */
    public function composeLocations(Request $request): array;
}
