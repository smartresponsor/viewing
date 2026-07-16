<?php

declare(strict_types=1);

namespace App\Viewing\ValueInterface\View;

interface ViewObjectPayloadInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toTemplateContext(): array;

    /**
     * @return array<string, mixed>
     */
    public function toFallbackData(): array;
}
