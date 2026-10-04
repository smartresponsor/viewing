<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewDecision;
use App\Viewing\Value\ViewPayload;
use App\Viewing\Value\ViewRequestContext;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines the public ViewJsonResponseFactory contract used across the Viewing presentation boundary.
 */
interface ViewJsonResponseFactoryInterface
{
    /**
     * Creates the typed Viewing result represented by this factory from normalized request data.
     */
    public function create(ViewPayload $payload, ViewRequestContext $context, ViewDecision $decision): JsonResponse;
}
