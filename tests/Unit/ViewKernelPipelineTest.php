<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Kernel;
use App\Viewing\Service\View\ViewDecisionService;
use App\Viewing\Service\View\ViewJsonResponseFactory;
use App\Viewing\Service\View\ViewJsonSerializer;
use App\Viewing\Service\View\ViewObjectPayloadNormalizer;
use App\Viewing\Service\View\ViewPayloadNormalizer;
use App\Viewing\Service\View\ViewRequestContextFactory;
use App\Viewing\Service\View\ViewResponseGuardService;
use App\Viewing\Service\View\ViewRouteExclusionService;
use App\Viewing\Service\View\ViewStatusCodeResolver;
use App\Viewing\Service\View\ViewTemplateCandidateService;
use App\Viewing\Service\View\ViewTemplateRenderer;
use App\Viewing\Service\View\ViewTemplateResolver;
use App\Viewing\Service\View\ViewTrafficClassifier;
use App\Viewing\ServiceInterface\View\ViewInterfaceLocationComposeServiceInterface;
use App\Viewing\Subscriber\View\ViewKernelResponseGuardSubscriber;
use App\Viewing\Subscriber\View\ViewKernelViewSubscriber;
use App\Viewing\Subscriber\View\ViewTrafficRequestSubscriber;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;
use Twig\Source;

#[RunTestsInSeparateProcesses]
final class ViewKernelPipelineTest extends TestCase
{
    public function testRealStandaloneKernelStillProvesBotJsonPath(): void
    {
        $kernel = new Kernel('test', true);

        try {
            $response = $kernel->handle(Request::create('/viewing', 'GET', server: [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_USER_AGENT' => 'ExampleBot/1.0',
            ]));

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertStringStartsWith('application/json', (string) $response->headers->get('Content-Type'));
            self::assertStringContainsString('actor_type_forces_json', (string) $response->getContent());
        } finally {
            $kernel->shutdown();
        }
    }

    public function testRealStandaloneKernelStillProvesHumanHtmlPath(): void
    {
        $kernel = new Kernel('test', true);

        try {
            $response = $kernel->handle(Request::create('/viewing', 'GET', server: [
                'HTTP_ACCEPT' => 'text/html',
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
                'HTTP_SEC_FETCH_MODE' => 'navigate',
            ]));

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertStringStartsWith('text/html', (string) $response->headers->get('Content-Type'));
            self::assertSame('1', $response->headers->get('X-Viewing-Rendered'));
        } finally {
            $kernel->shutdown();
        }
    }

    public function testExplicitJsonTraversesFullPipelineWithoutTwigLookup(): void
    {
        $probe = new ViewPipelineProbe();
        $request = Request::create('/vendor/1', 'GET', server: [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ]);
        $request->setRequestFormat('json');
        $request->attributes->set('_format', 'json');

        $response = $probe->run(
            $this->payload('vendor', 'show'),
            $request,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertStringContainsString('request_format_json', (string) $response->getContent());
        self::assertSame(0, $probe->renderedCount);
    }

    public function testUnknownPolicyCanForceJsonThroughRequestToViewPipeline(): void
    {
        $request = Request::create('/vendor/1', 'GET', server: [
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_USER_AGENT' => 'SdkClient/1.0',
        ]);
        $request->attributes->set('_view_controlled', false);

        $response = (new ViewPipelineProbe(unknownActorPolicy: 'json'))->run(
            $this->payload('vendor', 'show'),
            $request,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('unknown_actor_forces_json', (string) $response->getContent());
    }

    public function testMissingCandidateFallsBackToJsonWithOriginalStatus(): void
    {
        $response = (new ViewPipelineProbe(templates: [], diagnosticMode: 'off'))->run(
            $this->payload('vendor', 'show', meta: ['status_code' => 404]),
            Request::create('/vendor/1', 'GET', server: [
                'HTTP_ACCEPT' => 'text/html',
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
                'HTTP_SEC_FETCH_MODE' => 'navigate',
            ]),
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('template_missing_json_fallback', (string) $response->getContent());
    }

    public function testBrokenCandidateFallsBackToJsonFiveHundred(): void
    {
        $response = (new ViewPipelineProbe(templates: [
            '@Interfacing/vendor/index.html.twig' => '{{ broken_function() }}',
        ]))->run(
            $this->payload('vendor', 'show'),
            Request::create('/vendor/1', 'GET', server: [
                'HTTP_ACCEPT' => 'text/html',
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
                'HTTP_SEC_FETCH_MODE' => 'navigate',
            ]),
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('template_render_failed', (string) $response->getContent());
    }

    public function testLoaderFailureFallsBackToJsonFiveHundred(): void
    {
        $response = (new ViewPipelineProbe(loader: new ViewThrowingLoader()))->run(
            $this->payload('vendor', 'show'),
            Request::create('/vendor/1', 'GET', server: [
                'HTTP_ACCEPT' => 'text/html',
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
                'HTTP_SEC_FETCH_MODE' => 'navigate',
            ]),
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('template_loader_failed', (string) $response->getContent());
    }

    public function testLocalFallbackAndInterfacingPresentModesRenderHtml(): void
    {
        $probe = new ViewPipelineProbe(
            templates: ['@Vendor/index.html.twig' => '<html>{{ locations["shell.left.middle"][0].key }}</html>'],
            bridge: new ViewPipelineBridge(),
        );

        $response = $probe->run(
            $this->payload('missing', 'index', component: 'Vendor'),
            Request::create('/vendor', 'GET', server: [
                'HTTP_ACCEPT' => 'text/html',
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
                'HTTP_SEC_FETCH_MODE' => 'navigate',
            ]),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertStringStartsWith('text/html', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString('order', (string) $response->getContent());
    }

    public function testGuardObserveAndEnforceModesAreAppliedInResponseEvent(): void
    {
        $request = Request::create('/legacy');
        $request->attributes->set('_view_controlled', true);
        $request->attributes->set('_route', 'legacy_html');

        $observed = (new ViewPipelineProbe(guardMode: 'observe'))->guardOnly(
            $request,
            new Response('<html>legacy</html>', 200, ['Content-Type' => 'text/html']),
        );
        self::assertSame(200, $observed->getStatusCode());
        self::assertSame('observed-illegal-controller-render', $observed->headers->get('X-Viewing-Guard'));

        $enforced = (new ViewPipelineProbe(guardMode: 'enforce'))->guardOnly(
            $request,
            new Response('<html>legacy</html>', 200, ['Content-Type' => 'text/html']),
        );
        self::assertSame(500, $enforced->getStatusCode());
        self::assertSame('illegal-controller-render', $enforced->headers->get('X-Viewing-Guard'));
    }

    public function testGuardIgnoresExcludedRedirectBinaryStreamAndNoContentResponses(): void
    {
        $probe = new ViewPipelineProbe();
        $request = Request::create('/_profiler/page');
        $request->attributes->set('_view_controlled', true);
        $request->attributes->set('_route', '_profiler_home');

        self::assertNull($probe->guardOnly($request, new RedirectResponse('/next'))->headers->get('X-Viewing-Guard'));
        self::assertNull($probe->guardOnly($request, new StreamedResponse(static function (): void {}))->headers->get('X-Viewing-Guard'));
        self::assertNull($probe->guardOnly($request, new Response('', 204))->headers->get('X-Viewing-Guard'));

        $file = tempnam(sys_get_temp_dir(), 'viewing-r10-');
        self::assertIsString($file);
        file_put_contents($file, 'binary');
        try {
            self::assertNull($probe->guardOnly($request, new BinaryFileResponse($file))->headers->get('X-Viewing-Guard'));
        } finally {
            @unlink($file);
        }
    }

    public function testSubrequestDoesNotClassifyActor(): void
    {
        $probe = new ViewPipelineProbe();
        $request = Request::create('/vendor', server: ['HTTP_USER_AGENT' => 'ExampleBot/1.0']);
        $probe->dispatchRequest($request, HttpKernelInterface::SUB_REQUEST);

        self::assertNull($request->attributes->get('_view_actor_type'));
    }

    /**
     * @param array<string, mixed> $meta
     *
     * @return array<string, mixed>
     */
    private function payload(string $surface, string $operation, array $meta = [], ?string $component = null): array
    {
        return [
            '_view' => [
                'surface' => $surface,
                'operation' => $operation,
                'format' => 'auto',
                'component' => $component,
            ],
            'data' => ['label' => 'Vendor'],
            'meta' => $meta,
        ];
    }
}

final class ViewPipelineProbe
{
    public int $renderedCount = 0;

    private Kernel $kernel;
    private ViewTrafficRequestSubscriber $trafficSubscriber;
    private ViewKernelViewSubscriber $viewSubscriber;
    private ViewKernelResponseGuardSubscriber $guardSubscriber;
    private RequestStack $requestStack;

    /**
     * @param array<string, string> $templates
     */
    public function __construct(
        string $unknownActorPolicy = 'html',
        string $guardMode = 'enforce',
        array $templates = ['@Interfacing/vendor/index.html.twig' => '<html>{{ data.label|default("ok") }}</html>'],
        string $diagnosticMode = 'safe',
        ?LoaderInterface $loader = null,
        ?ViewInterfaceLocationComposeServiceInterface $bridge = null,
    ) {
        $this->kernel = new Kernel('test', true);
        $routeExclusion = new ViewRouteExclusionService(['#^/_profiler(?:/|$)#'], ['#^_profiler#']);
        $this->trafficSubscriber = new ViewTrafficRequestSubscriber(
            new ViewTrafficClassifier(['/bot/i']),
            $routeExclusion,
        );

        $this->requestStack = new RequestStack();
        $twig = new Environment($loader ?? new ArrayLoader($templates));
        $resolver = new ViewTemplateResolver($twig);
        $renderer = new ViewTemplateRenderer(
            $twig,
            $resolver,
            $this->requestStack,
            $bridge,
            new ViewStatusCodeResolver(),
        );
        $candidateService = new ViewTemplateCandidateService(diagnosticMode: $diagnosticMode);
        $this->viewSubscriber = new ViewKernelViewSubscriber(
            new ViewPayloadNormalizer(new ViewObjectPayloadNormalizer()),
            new ViewRequestContextFactory(),
            new ViewDecisionService(['bot'], $unknownActorPolicy),
            $candidateService,
            $renderer,
            new ViewJsonResponseFactory(
                statusCodeResolver: new ViewStatusCodeResolver(),
                jsonSerializer: new ViewJsonSerializer(),
            ),
        );
        $this->guardSubscriber = new ViewKernelResponseGuardSubscriber(
            new ViewResponseGuardService(guardMode: $guardMode),
            $routeExclusion,
        );
    }

    public function run(mixed $controllerResult, Request $request): Response
    {
        if (!$request->attributes->has('_route')) {
            $request->attributes->set('_route', 'r10_probe');
        }
        if (!$request->attributes->has('_view_controlled')) {
            $request->attributes->set('_view_controlled', true);
        }
        $this->dispatchRequest($request);

        $this->requestStack->push($request);
        try {
            $viewEvent = new ViewEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $controllerResult);
            $this->viewSubscriber->onKernelView($viewEvent);
            $response = $viewEvent->getResponse();
            if (null === $response) {
                $response = $controllerResult instanceof Response ? $controllerResult : new Response('', 204);
            }

            if ('1' === $response->headers->get('X-Viewing-Rendered')) {
                ++$this->renderedCount;
            }

            return $this->guardOnly($request, $response);
        } finally {
            $this->requestStack->pop();
        }
    }

    public function dispatchRequest(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST): void
    {
        $this->trafficSubscriber->onKernelRequest(new RequestEvent($this->kernel, $request, $type));
    }

    public function guardOnly(Request $request, Response $response): Response
    {
        $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $this->guardSubscriber->onKernelResponse($event);

        return $event->getResponse();
    }
}

final class ViewPipelineBridge implements ViewInterfaceLocationComposeServiceInterface
{
    public function composeLocations(Request $request): array
    {
        return ['shell.left.middle' => [['key' => 'order']]];
    }
}

final class ViewThrowingLoader implements LoaderInterface
{
    public function getSourceContext(string $name): Source
    {
        throw new \RuntimeException('loader unavailable');
    }

    public function getCacheKey(string $name): string
    {
        throw new \RuntimeException('loader unavailable');
    }

    public function isFresh(string $name, int $time): bool
    {
        throw new \RuntimeException('loader unavailable');
    }

    public function exists(string $name): bool
    {
        throw new \RuntimeException('loader unavailable');
    }
}
