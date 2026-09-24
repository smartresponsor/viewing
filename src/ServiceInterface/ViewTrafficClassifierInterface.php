<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use Symfony\Component\HttpFoundation\Request;

interface ViewTrafficClassifierInterface
{
    public function classify(Request $request): string;
}
