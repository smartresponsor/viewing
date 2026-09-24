<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

interface ViewJsonSerializerInterface
{
    /** @param array<string, mixed> $data */
    public function serialize(array $data): string;
}
