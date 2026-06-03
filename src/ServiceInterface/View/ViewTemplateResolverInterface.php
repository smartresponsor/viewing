<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface\View;

use App\Viewing\Value\View\ViewTemplateResolution;

interface ViewTemplateResolverInterface
{
    /**
     * @param list<string> $templateCandidates
     */
    public function resolve(array $templateCandidates): ViewTemplateResolution;
}
