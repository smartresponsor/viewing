<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewTemplateResolution;

/**
 * Defines the public ViewTemplateResolver contract used across the Viewing presentation boundary.
 */
interface ViewTemplateResolverInterface
{
    /**
     * Resolves the canonical Viewing value for the supplied payload and request context.
     *
     * @param list<string> $templateCandidates
     */
    public function resolve(array $templateCandidates): ViewTemplateResolution;
}
