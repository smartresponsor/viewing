<?php

declare(strict_types=1);

namespace App\Viewing\Value;

/**
 * Represents the TemplateFailureType immutable value used by the Viewing presentation decision pipeline.
 */
enum ViewTemplateFailureType: string
{
    case EmptyCandidateChain = 'empty_candidate_chain';
    case CandidateMissing = 'candidate_missing';
    case LoaderFailure = 'loader_failure';
    case RenderFailure = 'render_failure';
    case SystemicFailure = 'systemic_failure';
}
