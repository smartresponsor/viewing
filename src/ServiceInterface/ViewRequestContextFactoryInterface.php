<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewRequestContext;
use Symfony\Component\HttpFoundation\Request;

interface ViewRequestContextFactoryInterface
{
    public function create(Request $request): ViewRequestContext;
}
