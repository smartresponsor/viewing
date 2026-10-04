<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Factory\ViewJsonResponseFactory;
use App\Viewing\ServiceInterface\ViewJsonSerializerInterface;
use App\Viewing\ServiceInterface\ViewObservabilityServiceInterface;
use App\Viewing\Value\ViewDecision;
use App\Viewing\Value\ViewPayload;
use App\Viewing\Value\ViewRequestContext;
use PHPUnit\Framework\TestCase;

final class ViewJsonResponseFactoryTest extends TestCase
{
    public function testSerializationFailureReturnsControlledFiveHundredEnvelope(): void
    {
        $serializer = new class implements ViewJsonSerializerInterface {
            public function serialize(array $data): string
            {
                throw new \JsonException('recursion');
            }
        };
        $observability = $this->createMock(ViewObservabilityServiceInterface::class);
        $observability->expects(self::once())
            ->method('record')
            ->with(
                'json_serialization_failure',
                self::callback(static fn (array $context): bool => \JsonException::class === ($context['exception_class'] ?? null)),
                'error',
            );

        $factory = new ViewJsonResponseFactory(
            jsonSerializer: $serializer,
            observability: $observability,
        );

        $response = $factory->create(
            new ViewPayload('vendor', 'show'),
            new ViewRequestContext('/vendor/1', 'GET'),
            new ViewDecision(ViewDecision::MODE_JSON),
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('serialization_degraded', (string) $response->getContent());
    }

    public function testSafeAndDebugDiagnosticsProduceCompleteJsonResponses(): void
    {
        $payload = new ViewPayload('vendor', 'show', data: ['label' => 'Vendor']);
        $context = new ViewRequestContext('/vendor/1', 'GET', 'vendor_show', 'html', 'human');

        $safe = (new ViewJsonResponseFactory())->create(
            $payload,
            $context,
            new ViewDecision(ViewDecision::MODE_JSON, ['fallback']),
        );
        self::assertSame(200, $safe->getStatusCode());
        self::assertStringContainsString('"route":"vendor_show"', (string) $safe->getContent());

        $debug = (new ViewJsonResponseFactory(diagnosticMode: 'debug'))->create(
            $payload,
            $context,
            new ViewDecision(ViewDecision::MODE_JSON, ['fallback'], ['@Interfacing/vendor/index.html.twig']),
        );
        self::assertStringContainsString('template_candidates', (string) $debug->getContent());

        $off = (new ViewJsonResponseFactory(diagnosticMode: 'off'))->create(
            $payload,
            $context,
            new ViewDecision(ViewDecision::MODE_JSON),
        );
        self::assertStringNotContainsString('"route"', (string) $off->getContent());
    }
}
