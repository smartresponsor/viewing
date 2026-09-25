<?php

declare(strict_types=1);

namespace ExternalFixture;

use App\Viewing\ValueObjectInterface\ViewObjectPayloadInterface;

final class ExternalViewObjectPayload implements ViewObjectPayloadInterface
{
    public function toTemplateContext(): array
    {
        return [
            'word' => 'external',
            'view' => 'index',
        ];
    }

    public function toFallbackData(): array
    {
        return [];
    }
}
