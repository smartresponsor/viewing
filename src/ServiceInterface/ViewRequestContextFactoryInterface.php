<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewRequestContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the public ViewRequestContextFactory contract used across the Viewing presentation boundary.
 */
interface ViewRequestContextFactoryInterface
{
    /**
     * Creates the typed Viewing result represented by this factory from normalized request data.
     */
    public function create(Request $request): ViewRequestContext;
}
