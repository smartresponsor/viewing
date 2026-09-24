<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ViewResponseGuardServiceInterface
{
    public function mode(): string;

    public function isViolation(Request $request, Response $response): bool;

    public function observe(Response $response): Response;

    public function replacement(Request $request, Response $response): Response;
}
