<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface\View;

use Symfony\Component\HttpFoundation\Request;

interface ViewTrafficClassifierInterface
{
    public function classify(Request $request): ?string;
}
