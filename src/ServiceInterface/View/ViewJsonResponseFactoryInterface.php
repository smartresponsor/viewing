<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface\View;

use App\Viewing\Value\View\ViewDecision;
use App\Viewing\Value\View\ViewPayload;
use App\Viewing\Value\View\ViewRequestContext;
use Symfony\Component\HttpFoundation\JsonResponse;

interface ViewJsonResponseFactoryInterface
{
    public function create(ViewPayload $payload, ViewRequestContext $context, ViewDecision $decision): JsonResponse;
}
