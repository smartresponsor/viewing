<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

require_once \dirname(__DIR__).'/Fixture/ExternalViewObjectPayload.php';

use App\Viewing\Normalizer\ViewPayloadNormalizer;
use App\Viewing\ValueObjectInterface\ViewObjectPayloadInterface;
use ExternalFixture\ExternalViewObjectPayload;
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
        self::assertFalse($normalizer->supports('not-a-payload'));
        self::assertTrue($normalizer->supports(new \App\Viewing\Value\ViewPayload('vendor', 'index')));
        self::assertTrue($normalizer->supports(['_surface' => 'vendor']));
    }

    public function testReturnsExistingViewPayloadUnchanged(): void
    {
        $normalizer = new ViewPayloadNormalizer();
        $payload = new \App\Viewing\Value\ViewPayload('vendor', 'index');

        self::assertSame($payload, $normalizer->normalize($payload));
    }

    public function testRejectsUnsupportedAndIncompleteArrayPayloads(): void
    {
        $normalizer = new ViewPayloadNormalizer();

        try {
            $normalizer->normalize('invalid');
            self::fail('Unsupported scalar payload must fail.');
        } catch (\App\Viewing\Exception\ViewPayloadException $exception) {
            self::assertStringContainsString('Unsupported controller result', $exception->getMessage());
        }

        try {
            $normalizer->normalize(['_view' => ['operation' => 'show']]);
            self::fail('Missing surface must fail.');
        } catch (\App\Viewing\Exception\ViewPayloadException $exception) {
            self::assertStringContainsString('_view.surface', $exception->getMessage());
        }

        $this->expectException(\App\Viewing\Exception\ViewPayloadException::class);
        $normalizer->normalize(['_view' => ['surface' => 'vendor']]);
    }

    public function testLegacyArrayPayloadUsesFallbackFieldsAndSafeDefaults(): void
    {
        $normalizer = new ViewPayloadNormalizer();

        $payload = $normalizer->normalize([
            '_surface' => ' vendor ',
            '_operation' => ' index ',
            '_format' => [],
            '_intent' => [],
            '_component' => [],
            'locations' => ['shell.main.content' => [['label' => 'legacy']]],
            'data' => 'invalid',
            'meta' => 'invalid',
            'debug' => 'invalid',
        ]);

        self::assertSame('vendor', $payload->surface);
        self::assertSame('index', $payload->operation);
        self::assertSame('auto', $payload->format);
        self::assertNull($payload->intent);
        self::assertNull($payload->component);
        self::assertSame('legacy', $payload->locations['shell.main.content'][0]['label'] ?? null);
        self::assertSame([], $payload->data);
        self::assertSame([], $payload->meta);
        self::assertSame([], $payload->debug);
    }

    public function testObjectPayloadSupportsAllCanonicalRouteAndLocationFallbackSources(): void
    {
        $normalizer = new ViewPayloadNormalizer();

        $cases = [
            [
                ['word' => [], 'view' => ' ', 'format' => null],
                [
                    'workbench' => ['routeContext' => ['resourcePath' => 'orders', 'operation' => 'show']],
                    'interface' => ['locations' => ['slot' => [['label' => 'fallback-interface']]]],
                    'meta' => ['source_note' => 'fallback'],
                ],
                'orders',
                'show',
                'fallback-interface',
            ],
            [
                ['word' => 'customer', 'view' => 'detail', 'routeContext' => ['resource' => 'customers'], 'locations' => ['slot' => [['label' => 'template-direct']]]],
                [],
                'customers',
                'detail',
                'template-direct',
            ],
            [
                [],
                ['routeContext' => ['viewPath' => 'reports', 'operation' => 'index'], 'locations' => ['slot' => [['label' => 'fallback-direct']]]],
                'reports',
                'index',
                'fallback-direct',
            ],
        ];

        foreach ($cases as [$template, $fallback, $surface, $operation, $label]) {
            $payload = $normalizer->normalize($this->objectPayload($template, $fallback));

            self::assertSame($surface, $payload->surface);
            self::assertSame($operation, $payload->operation);
            self::assertSame($label, $payload->locations['slot'][0]['label'] ?? null);
        }
    }

    public function testObjectPayloadRouteContextChecksSurfaceAndResourceKeysInOrder(): void
    {
        $normalizer = new ViewPayloadNormalizer();

        foreach ([
            ['surfacePath' => 'surface-path'],
            ['resourcePath' => 'resource-path'],
            ['resource' => 'resource'],
        ] as $routeContext) {
            $payload = $normalizer->normalize($this->objectPayload(
                ['word' => 'fallback', 'view' => 'index', 'workbench' => ['routeContext' => $routeContext]],
                [],
            ));

            self::assertSame((string) array_values($routeContext)[0], $payload->surface);
        }
    }

    public function testObjectPayloadFallsThroughEmptyRouteValuesAndUsesStringFallbacks(): void
    {
        $normalizer = new ViewPayloadNormalizer();

        $payload = $normalizer->normalize($this->objectPayload(
            [
                'word' => 'fallback-surface',
                'view' => 'fallback-operation',
                'workbench' => [
                    'routeContext' => [
                        'viewPath' => '   ',
                        'surfacePath' => [],
                        'resourcePath' => '',
                        'resource' => 'resolved-resource',
                        'operation' => '   ',
                    ],
                ],
            ],
            [],
        ));

        self::assertSame('resolved-resource', $payload->surface);
        self::assertSame('fallback-operation', $payload->operation);

        $fallback = $normalizer->normalize($this->objectPayload(
            ['word' => 'fallback-surface', 'view' => 'fallback-operation'],
            [],
        ));
        self::assertSame('fallback-surface', $fallback->surface);
        self::assertSame('fallback-operation', $fallback->operation);
        self::assertSame([], $fallback->locations);
    }

    public function testArrayPayloadWithoutLocationsUsesEmptyLocationMap(): void
    {
        $payload = (new ViewPayloadNormalizer())->normalize([
            '_view' => ['surface' => 'vendor', 'operation' => 'index'],
        ]);

        self::assertSame([], $payload->locations);
    }

    public function testForeignContractPayloadRemainsOutsidePlatformComponentIdentity(): void
    {
        $payload = (new ViewPayloadNormalizer())->normalize(new ExternalViewObjectPayload());

        self::assertSame('external', $payload->surface);
        self::assertSame('index', $payload->operation);
        self::assertNull($payload->component);
        self::assertSame(ExternalViewObjectPayload::class, $payload->data['objectClass'] ?? null);
    }

    public function testObjectNormalizerClassIdentityHelpersCoverPlatformAndForeignClasses(): void
    {
        $normalizer = new \App\Viewing\Normalizer\ViewObjectPayloadNormalizer();

        $componentFromClass = new \ReflectionMethod($normalizer, 'componentFromClass');
        $componentFromClass->setAccessible(true);
        self::assertSame('Viewing', $componentFromClass->invoke($normalizer, self::class));
        self::assertNull($componentFromClass->invoke($normalizer, \stdClass::class));

        $viewFromClass = new \ReflectionMethod($normalizer, 'viewFromClass');
        $viewFromClass->setAccessible(true);
        self::assertSame('view-payload-normalizer-test', $viewFromClass->invoke($normalizer, self::class));
        self::assertSame('std-class', $viewFromClass->invoke($normalizer, \stdClass::class));
    }

    /**
     * @param array<string, mixed> $template
     * @param array<string, mixed> $fallback
     */
    private function objectPayload(array $template, array $fallback): ViewObjectPayloadInterface
    {
        return new class($template, $fallback) implements ViewObjectPayloadInterface {
            /**
             * @param array<string, mixed> $template
             * @param array<string, mixed> $fallback
             */
            public function __construct(
                private readonly array $template,
                private readonly array $fallback,
            ) {
            }

            public function toTemplateContext(): array
            {
                return $this->template;
            }

            public function toFallbackData(): array
            {
                return $this->fallback;
            }
        };
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
