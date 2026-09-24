<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewDecision;
use App\Viewing\Value\ViewPayload;
use App\Viewing\Value\ViewRequestContext;

interface ViewDecisionServiceInterface
{
    public function decide(ViewPayload $payload, ViewRequestContext $context): ViewDecision;
}
