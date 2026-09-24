<?php

declare(strict_types=1);

namespace App\Viewing\Value;

enum ViewActorType: string
{
    case Bot = 'bot';
    case Human = 'human';
    case Unknown = 'unknown';
}
