<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewDecision;
use App\Viewing\Value\ViewPayload;
use App\Viewing\Value\ViewRequestContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the public ViewTemplateRenderer contract used across the Viewing presentation boundary.
 */
interface ViewTemplateRendererInterface
{
    /**
     * Renders the selected template with normalized context while preserving Viewing fallback semantics.
     */
    public function render(ViewPayload $payload, ViewRequestContext $context, ViewDecision $decision): ?Response;
}
