<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Factory\ViewRequestContextFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ViewRequestContextFactoryTest extends TestCase
{
    public function testFactoryNormalizesRouteActorAcceptAndXhrSignals(): void
    {
        $factory = new ViewRequestContextFactory();

        $html = Request::create('/vendor', 'GET', server: ['HTTP_ACCEPT' => 'text/html']);
        $html->attributes->set('_route', 'vendor_index');
        $html->attributes->set('_view_actor_type', ' human ');
        $htmlContext = $factory->create($html);

        self::assertSame('vendor_index', $htmlContext->routeName);
        self::assertSame('human', $htmlContext->actorType);
        self::assertTrue($htmlContext->prefersHtml);
        self::assertFalse($htmlContext->prefersJson);

        $json = Request::create('/vendor', 'POST', server: [
            'HTTP_ACCEPT' => 'application/problem+json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);
        $json->attributes->set('_route', 123);
        $json->attributes->set('_view_actor_type', '   ');
        $jsonContext = $factory->create($json);

        self::assertNull($jsonContext->routeName);
        self::assertNull($jsonContext->actorType);
        self::assertFalse($jsonContext->prefersHtml);
        self::assertTrue($jsonContext->prefersJson);
        self::assertTrue($jsonContext->xmlHttpRequest);

        $applicationJson = Request::create('/vendor', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
        $applicationJsonContext = $factory->create($applicationJson);
        self::assertTrue($applicationJsonContext->prefersJson);
        self::assertFalse($applicationJsonContext->prefersHtml);

        $default = $factory->create(Request::create('/vendor'));
        self::assertTrue($default->prefersHtml);
    }

    public function testFactoryHandlesEmptyAcceptableTypesAndNullRequestFormat(): void
    {
        $factory = new ViewRequestContextFactory();

        $emptyAccept = new class extends Request {
            public function getAcceptableContentTypes(): array
            {
                return [];
            }
        };
        $emptyAccept->server->set('REQUEST_URI', '/empty-accept');
        self::assertTrue($factory->create($emptyAccept)->prefersHtml);

        $nullFormat = new class extends Request {
            public function getRequestFormat(?string $default = 'html'): ?string
            {
                return null;
            }
        };
        $nullFormat->server->set('REQUEST_URI', '/null-format');
        self::assertSame('html', $factory->create($nullFormat)->requestFormat);

        $both = new class extends Request {
            public function getAcceptableContentTypes(): array
            {
                return [];
            }

            public function getRequestFormat(?string $default = 'html'): ?string
            {
                return null;
            }
        };
        $both->server->set('REQUEST_URI', '/both');
        $context = $factory->create($both);

        self::assertTrue($context->prefersHtml);
        self::assertSame('html', $context->requestFormat);
    }
}
