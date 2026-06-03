<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface\View;

use App\Viewing\Value\View\ViewPayload;
use App\Viewing\Value\View\ViewRequestContext;

interface ViewTemplateCandidateServiceInterface
{
    /**
     * @return list<string>
     */
    public function candidates(ViewPayload $payload, ViewRequestContext $context): array;
}
