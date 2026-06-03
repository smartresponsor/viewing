<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface\View;

use App\Viewing\Value\View\ViewPayload;

interface ViewPayloadNormalizerInterface
{
    public function supports(mixed $controllerResult): bool;

    public function normalize(mixed $controllerResult): ViewPayload;
}
