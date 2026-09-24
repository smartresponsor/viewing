<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewTemplateResolution;

interface ViewTemplateResolverInterface
{
    /**
     * @param list<string> $templateCandidates
     */
    public function resolve(array $templateCandidates): ViewTemplateResolution;
}
