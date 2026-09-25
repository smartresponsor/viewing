<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewPayload;
use App\Viewing\Value\ViewRequestContext;

/**
 * Defines the public ViewTemplateCandidateService contract used across the Viewing presentation boundary.
 */
interface ViewTemplateCandidateServiceInterface
{
    /**
     * Builds the ordered canonical template candidate chain for the normalized view payload.
     *
     * @return list<string>
     */
    public function candidates(ViewPayload $payload, ViewRequestContext $context): array;
}
