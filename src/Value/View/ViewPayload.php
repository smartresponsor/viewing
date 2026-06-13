<?php

declare(strict_types=1);

namespace App\Viewing\Value\View;

final readonly class ViewPayload
{
    /**
     * @param array<string, mixed> $locations
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $debug
     */
    public function __construct(
        public string $surface,
        public string $operation,
        public string $format = 'auto',
        public ?string $intent = null,
        public ?string $component = null,
        public array $locations = [],
        public array $data = [],
        public array $meta = [],
        public array $debug = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            '_view' => [
                'surface' => $this->surface,
                'operation' => $this->operation,
                'intent' => $this->intent,
                'format' => $this->format,
                'component' => $this->component,
            ],
            'interface' => [
                'locations' => $this->locations,
            ],
            'locations' => $this->locations,
            'data' => $this->data,
            'meta' => $this->meta,
            'debug' => $this->debug,
        ];
    }
}
