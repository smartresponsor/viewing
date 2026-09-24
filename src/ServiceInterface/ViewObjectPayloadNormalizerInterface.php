<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Interfacing\Contract\InterfaceSurfaceRenderableInterface;
use App\Viewing\Value\ViewPayload;
use App\Viewing\ValueObjectInterface\ViewObjectPayloadInterface;

interface ViewObjectPayloadNormalizerInterface
{
    public function supports(mixed $value): bool;

    public function normalize(ViewObjectPayloadInterface|InterfaceSurfaceRenderableInterface $viewObject): ViewPayload;
}
