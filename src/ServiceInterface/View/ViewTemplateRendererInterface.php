<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface\View;

use App\Viewing\Value\View\ViewDecision;
use App\Viewing\Value\View\ViewPayload;
use App\Viewing\Value\View\ViewRequestContext;
use Symfony\Component\HttpFoundation\Response;

interface ViewTemplateRendererInterface
{
    public function render(ViewPayload $payload, ViewRequestContext $context, ViewDecision $decision): ?Response;
}
