<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewPayload;

interface ViewPayloadNormalizerInterface
{
    public function supports(mixed $controllerResult): bool;

    public function normalize(mixed $controllerResult): ViewPayload;
}
