<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface\View;

use App\Viewing\Value\View\ViewPayload;

interface ViewObjectPayloadNormalizerInterface
{
    public function supports(mixed $value): bool;

    public function normalize(object $viewObject): ViewPayload;
}
