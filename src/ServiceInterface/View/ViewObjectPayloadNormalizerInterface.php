<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface\View;

use App\Interfacing\Contract\InterfaceSurfaceRenderableInterface;
use App\Viewing\Value\View\ViewPayload;
use App\Viewing\ValueInterface\View\ViewObjectPayloadInterface;

interface ViewObjectPayloadNormalizerInterface
{
    public function supports(mixed $value): bool;

    public function normalize(ViewObjectPayloadInterface|InterfaceSurfaceRenderableInterface $viewObject): ViewPayload;
}
