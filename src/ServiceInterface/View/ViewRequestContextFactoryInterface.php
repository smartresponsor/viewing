<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface\View;

use App\Viewing\Value\View\ViewRequestContext;
use Symfony\Component\HttpFoundation\Request;

interface ViewRequestContextFactoryInterface
{
    public function create(Request $request): ViewRequestContext;
}
