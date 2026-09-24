<?php

declare(strict_types=1);

namespace App\Viewing\ValueObjectInterface;

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
