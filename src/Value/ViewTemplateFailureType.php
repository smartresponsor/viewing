<?php

declare(strict_types=1);

namespace App\Viewing\Value;

enum ViewTemplateFailureType: string
{
    case EmptyCandidateChain = 'empty_candidate_chain';
    case CandidateMissing = 'candidate_missing';
    case LoaderFailure = 'loader_failure';
    case RenderFailure = 'render_failure';
    case SystemicFailure = 'systemic_failure';
}
