<?php

declare(strict_types=1);

namespace App\Viewing\Contract;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the public ViewInterfaceLocationComposer contract used across the Viewing presentation boundary.
 */
interface ViewInterfaceLocationComposerInterface
{
    /**
     * Composes Interfacing location data for the current request without transferring rendering ownership.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function composeLocations(Request $request): array;
}
