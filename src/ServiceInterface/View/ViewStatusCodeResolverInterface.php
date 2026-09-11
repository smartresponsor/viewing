<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface\View;

use App\Viewing\Value\View\ViewPayload;

interface ViewStatusCodeResolverInterface
{
    public function resolve(ViewPayload $payload, ?int $override = null, int $fallback = 200): int;
}
