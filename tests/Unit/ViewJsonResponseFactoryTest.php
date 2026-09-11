<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Service\View\ViewJsonResponseFactory;
use App\Viewing\ServiceInterface\View\ViewJsonSerializerInterface;
use App\Viewing\ServiceInterface\View\ViewObservabilityServiceInterface;
use App\Viewing\Value\View\ViewDecision;
use App\Viewing\Value\View\ViewPayload;
use App\Viewing\Value\View\ViewRequestContext;
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
}
