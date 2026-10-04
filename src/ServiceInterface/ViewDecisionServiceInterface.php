<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewDecision;
use App\Viewing\Value\ViewPayload;
use App\Viewing\Value\ViewRequestContext;

/**
 * Defines the public ViewDecisionService contract used across the Viewing presentation boundary.
 */
interface ViewDecisionServiceInterface
{
    /**
     * Selects the HTML or JSON representation policy from payload and request classification evidence.
     */
    public function decide(ViewPayload $payload, ViewRequestContext $context): ViewDecision;
}
