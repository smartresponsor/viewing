<?php

declare(strict_types=1);

namespace App\Viewing\Contract;

use Symfony\Component\HttpFoundation\Request;

interface ViewInterfaceLocationComposerInterface
{
    /** @return array<string, list<array<string, mixed>>> */
    public function composeLocations(Request $request): array;
}
