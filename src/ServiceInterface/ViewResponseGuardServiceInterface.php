<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the public ViewResponseGuardService contract used across the Viewing presentation boundary.
 */
interface ViewResponseGuardServiceInterface
{
    /**
     * Returns the configured response-guard mode that controls drift observation or enforcement.
     */
    public function mode(): string;

    public function isViolation(Request $request, Response $response): bool;

    /**
     * Builds the non-destructive response used to expose an observed presentation-boundary violation.
     */
    public function observe(Response $response): Response;

    /**
     * Builds the controlled replacement response used when an illegal HTML response is enforced.
     */
    public function replacement(Request $request, Response $response): Response;
}
