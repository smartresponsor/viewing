<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Contract\ViewInterfaceLocationComposerInterface;
use App\Viewing\Renderer\ViewTemplateRenderer;
use App\Viewing\ServiceInterface\ViewTemplateResolverInterface;
use App\Viewing\Value\ViewDecision;
use App\Viewing\Value\ViewPayload;
use App\Viewing\Value\ViewRequestContext;
use App\Viewing\Value\ViewTemplateResolution;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class ViewTemplateRendererTest extends TestCase
{
    public function testTemplateThrowableFallsBackToJsonPath(): void
    {
        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willThrowException(new \RuntimeException('twig boom'));

        $resolver = $this->createStub(ViewTemplateResolverInterface::class);
        $resolver->method('resolve')->willReturn(new ViewTemplateResolution(
            selectedTemplate: 'vendor/index.html.twig',
            checkedCandidates: [['template' => 'vendor/index.html.twig', 'exists' => true]],
            availableCandidates: ['vendor/index.html.twig'],
            missingCandidates: [],
        ));

        $renderer = new ViewTemplateRenderer($twig, $resolver, new RequestStack());
        $payload = new ViewPayload(surface: 'vendor', operation: 'index');
        $context = new ViewRequestContext('/vendor/index', 'GET');
        $decision = new ViewDecision(ViewDecision::MODE_HTML);

        self::assertNull($renderer->render($payload, $context, $decision));
    }

    public function testStatusCodeDefaultsToOkWhenPayloadHasNoExplicitCode(): void
    {
        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willReturn('<html></html>');

        $resolver = $this->createStub(ViewTemplateResolverInterface::class);
        $resolver->method('resolve')->willReturn(new ViewTemplateResolution(
            selectedTemplate: 'vendor/index.html.twig',
            checkedCandidates: [['template' => 'vendor/index.html.twig', 'exists' => true]],
            availableCandidates: ['vendor/index.html.twig'],
            missingCandidates: [],
        ));

        $renderer = new ViewTemplateRenderer($twig, $resolver, new RequestStack());
        $payload = new ViewPayload(surface: 'vendor', operation: 'index');
        $context = new ViewRequestContext('/vendor/index', 'GET');
        $decision = new ViewDecision(ViewDecision::MODE_HTML);

        $response = $renderer->render($payload, $context, $decision);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testAppComposedLocationReplacesProducerLocationWithoutDuplicatingItems(): void
    {
        $twig = $this->createStub(Environment::class);
        $resolver = $this->createStub(ViewTemplateResolverInterface::class);
        $renderer = new ViewTemplateRenderer($twig, $resolver, new RequestStack());

        $method = new \ReflectionMethod($renderer, 'mergeLocations');
        $method->setAccessible(true);

        $locations = $method->invoke($renderer, [
            'shell.left.middle' => [
                ['key' => 'vendor', 'href' => '/vendor/index'],
            ],
            'shell.main.toolbar' => [
                ['key' => 'producer-action'],
            ],
        ], [
            'shell.left.middle' => [
                ['key' => 'vendor', 'href' => '/vendor/index'],
                ['key' => 'order', 'href' => '/order/index'],
            ],
        ]);

        self::assertSame([
            ['key' => 'vendor', 'href' => '/vendor/index'],
            ['key' => 'order', 'href' => '/order/index'],
        ], $locations['shell.left.middle']);
        self::assertSame([
            ['key' => 'producer-action'],
        ], $locations['shell.main.toolbar']);
    }

    public function testOptionalInterfacingBridgeComposesLocationsWhenPresent(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with('vendor/index.html.twig', self::callback(static fn (array $context): bool => 'order' === ($context['locations']['shell.left.middle'][0]['key'] ?? null)))
            ->willReturn('<html></html>');

        $resolver = $this->createStub(ViewTemplateResolverInterface::class);
        $resolver->method('resolve')->willReturn(new ViewTemplateResolution(
            selectedTemplate: 'vendor/index.html.twig',
            checkedCandidates: [['template' => 'vendor/index.html.twig', 'exists' => true]],
            availableCandidates: ['vendor/index.html.twig'],
            missingCandidates: [],
        ));

        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/vendor'));
        $bridge = new class implements ViewInterfaceLocationComposerInterface {
            public function composeLocations(Request $request): array
            {
                return ['shell.left.middle' => [['key' => 'order']]];
            }
        };

        $renderer = new ViewTemplateRenderer($twig, $resolver, $requestStack, $bridge);
        $response = $renderer->render(
            new ViewPayload('vendor', 'index'),
            new ViewRequestContext('/vendor', 'GET'),
            new ViewDecision(ViewDecision::MODE_HTML, templateCandidates: ['vendor/index.html.twig']),
        );

        self::assertInstanceOf(Response::class, $response);
    }

    public function testRendererKeepsFailureTraceAndFallsThroughToNextCandidate(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::exactly(2))
            ->method('render')
            ->willReturnCallback(static function (string $template): string {
                if ('broken.html.twig' === $template) {
                    throw new \RuntimeException('broken template');
                }

                return '<html>ok</html>';
            });

        $resolver = $this->createStub(ViewTemplateResolverInterface::class);
        $resolver->method('resolve')->willReturn(new ViewTemplateResolution(
            selectedTemplate: 'broken.html.twig',
            checkedCandidates: [
                ['template' => 'loader-failed.html.twig', 'exists' => false, 'error' => \RuntimeException::class],
                ['template' => 'broken.html.twig', 'exists' => true],
                ['template' => 'working.html.twig', 'exists' => true],
            ],
            availableCandidates: ['broken.html.twig', 'working.html.twig'],
            missingCandidates: [],
            loaderFailures: [
                ['template' => 'loader-failed.html.twig', 'exception' => \RuntimeException::class],
            ],
        ));

        $statusResolver = $this->createStub(\App\Viewing\ServiceInterface\ViewStatusCodeResolverInterface::class);
        $statusResolver->method('resolve')->willReturn(Response::HTTP_CREATED);

        $observability = $this->createStub(\App\Viewing\ServiceInterface\ViewObservabilityServiceInterface::class);

        $request = Request::create('/vendor');
        $request->attributes->set('_app_crud_contract_ms', '1.23');
        $request->attributes->set('_view_render_failures', [[
            'template' => 'prior.html.twig',
            'exception' => \RuntimeException::class,
            'message' => 'prior failure',
        ]]);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $renderer = new ViewTemplateRenderer(
            $twig,
            $resolver,
            $requestStack,
            statusCodeResolver: $statusResolver,
            observability: $observability,
        );

        $response = $renderer->render(
            new ViewPayload('vendor', 'index'),
            new ViewRequestContext('/vendor', 'GET'),
            new ViewDecision(
                ViewDecision::MODE_HTML,
                templateCandidates: ['loader-failed.html.twig', 'broken.html.twig', 'working.html.twig'],
            ),
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame('working.html.twig', $response->headers->get('X-Viewing-Template'));
        self::assertFalse($response->headers->has('X-App-Crud-Contract-ms'));
        self::assertFalse($response->headers->has('X-Crud-Context-ms'));
        self::assertNotEmpty($request->attributes->get('_view_loader_failures'));
        self::assertCount(2, $request->attributes->get('_view_render_failures'));
    }
}
