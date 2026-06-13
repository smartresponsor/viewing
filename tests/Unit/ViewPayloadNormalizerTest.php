<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Service\View\ViewPayloadNormalizer;
use PHPUnit\Framework\TestCase;

final class ViewPayloadNormalizerTest extends TestCase
{
    public function testNormalizesCanonicalArrayPayload(): void
    {
        $normalizer = new ViewPayloadNormalizer();

        $payload = $normalizer->normalize([
            '_view' => [
                'surface' => 'vendor',
                'operation' => 'show',
                'intent' => 'profile',
                'component' => 'Cruding',
            ],
            'interface' => [
                'locations' => [
                    'shell.main.content' => [['type' => 'text', 'label' => 'Vendor']],
                ],
            ],
            'meta' => ['title' => 'Vendor'],
        ]);

        self::assertSame('vendor', $payload->surface);
        self::assertSame('show', $payload->operation);
        self::assertSame('profile', $payload->intent);
        self::assertSame('Cruding', $payload->component);
        self::assertSame(['title' => 'Vendor'], $payload->meta);
        self::assertSame('Vendor', $payload->locations['shell.main.content'][0]['label'] ?? null);
    }

    public function testNormalizesSurfaceObjectUsingRouteContextSurfaceBeforeWord(): void
    {
        $normalizer = new ViewPayloadNormalizer();

        $payload = $normalizer->normalize(new ViewPayloadNormalizerSurfaceStub());

        self::assertSame('compliance', $payload->surface);
        self::assertSame('briefing', $payload->operation);
        self::assertSame('surface', $payload->intent);
        self::assertSame('main payload', $payload->locations['shell.main.content'][0]['label'] ?? null);
        self::assertSame('crud', $payload->data['word'] ?? null);
        self::assertSame('compliance', $payload->data['routeContext']['surfacePath'] ?? null);
    }
}

final class ViewPayloadNormalizerSurfaceStub
{
    /**
     * @return array<string, mixed>
     */
    public function toTemplateContext(): array
    {
        return [
            'word' => 'crud',
            'view' => 'briefing',
            'workbench' => [
                'routeContext' => [
                    'resource' => 'vendor',
                    'resourcePath' => 'vendor',
                    'surfacePath' => 'compliance',
                    'operation' => 'briefing',
                ],
            ],
            'interface' => [
                'locations' => [
                    'shell.main.content' => [['type' => 'text', 'label' => 'main payload']],
                ],
            ],
            'meta' => ['title' => 'Compliance'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFallbackData(): array
    {
        return [
            'word' => 'crud',
            'view' => 'briefing',
            'interface' => ['locations' => []],
        ];
    }
}
