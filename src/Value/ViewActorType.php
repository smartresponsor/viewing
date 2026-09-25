<?php

declare(strict_types=1);

namespace App\Viewing\Value;

/**
 * Represents the ActorType immutable value used by the Viewing presentation decision pipeline.
 */
enum ViewActorType: string
{
    case Bot = 'bot';
    case Human = 'human';
    case Unknown = 'unknown';
}
