<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewPayload;

interface ViewStatusCodeResolverInterface
{
    public function resolve(ViewPayload $payload, ?int $override = null, int $fallback = 200): int;
}
