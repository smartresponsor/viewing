<?php

declare(strict_types=1);

namespace App\Viewing\Service;

use App\Viewing\ServiceInterface\ViewJsonSerializerInterface;

final readonly class ViewJsonSerializer implements ViewJsonSerializerInterface
{
    public function serialize(array $data): string
    {
        return json_encode(
            $data,
            \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_THROW_ON_ERROR,
            64,
        );
    }
}
