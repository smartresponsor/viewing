<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewDecision;
use App\Viewing\Value\ViewPayload;
use App\Viewing\Value\ViewRequestContext;
use Symfony\Component\HttpFoundation\Response;

interface ViewTemplateRendererInterface
{
    public function render(ViewPayload $payload, ViewRequestContext $context, ViewDecision $decision): ?Response;
}
