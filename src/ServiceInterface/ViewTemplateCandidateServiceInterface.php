<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewPayload;
use App\Viewing\Value\ViewRequestContext;

interface ViewTemplateCandidateServiceInterface
{
    /**
     * @return list<string>
     */
    public function candidates(ViewPayload $payload, ViewRequestContext $context): array;
}
