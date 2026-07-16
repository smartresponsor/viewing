<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Service\View\ViewPayloadNormalizer;
use App\Viewing\ValueInterface\View\ViewObjectPayloadInterface;
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

    public function testNormalizesObjectPayloadUsingRouteContextBeforeWord(): void
    {
        $normalizer = new ViewPayloadNormalizer();

        $payload = $normalizer->normalize(new ViewPayloadNormalizerObjectStub());

        self::assertSame('compliance', $payload->surface);
        self::assertSame('briefing', $payload->operation);
        self::assertSame('object', $payload->intent);
        self::assertSame('main payload', $payload->locations['shell.main.content'][0]['label'] ?? null);
        self::assertSame('crud', $payload->data['word'] ?? null);
        self::assertSame('compliance', $payload->data['routeContext']['surfacePath'] ?? null);
    }

    public function testRejectsDuckTypedObjectWithoutExplicitContract(): void
    {
        $normalizer = new ViewPayloadNormalizer();

        self::assertFalse($normalizer->supports(new ViewPayloadNormalizerDuckTypedStub()));
    }
}

final class ViewPayloadNormalizerObjectStub implements ViewObjectPayloadInterface
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

final class ViewPayloadNormalizerDuckTypedStub
{
    /** @return array<string, mixed> */
    public function toTemplateContext(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function toFallbackData(): array
    {
        return [];
    }
}
