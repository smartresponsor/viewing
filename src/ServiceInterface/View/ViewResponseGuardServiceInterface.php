<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface\View;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ViewResponseGuardServiceInterface
{
    public function shouldReplace(Request $request, Response $response): bool;

    public function replacement(Request $request, Response $response): Response;
}
