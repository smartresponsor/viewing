<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface\View;

use App\Viewing\Value\View\ViewDecision;
use App\Viewing\Value\View\ViewPayload;
use App\Viewing\Value\View\ViewRequestContext;

interface ViewDecisionServiceInterface
{
    public function decide(ViewPayload $payload, ViewRequestContext $context): ViewDecision;
}
