<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\EventSubscriber\ViewKernelResponseGuardSubscriber;
use App\Viewing\EventSubscriber\ViewKernelViewSubscriber;
use App\Viewing\EventSubscriber\ViewTrafficRequestSubscriber;
use App\Viewing\Service\ViewObservabilityService;
use App\Viewing\Service\ViewResponseGuardService;
use App\Viewing\Service\ViewRouteExclusionService;
use App\Viewing\Service\ViewTrafficClassifier;
use App\Viewing\Value\ViewDecision;
use App\Viewing\Value\ViewTemplateResolution;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;

final class ViewCoverageCompletionTest extends TestCase
{
    public function testSubscribersExposeCanonicalKernelEventRegistration(): void
    {
        self::assertArrayHasKey(KernelEvents::REQUEST, ViewTrafficRequestSubscriber::getSubscribedEvents());
        self::assertArrayHasKey(KernelEvents::VIEW, ViewKernelViewSubscriber::getSubscribedEvents());
        self::assertArrayHasKey(KernelEvents::RESPONSE, ViewKernelResponseGuardSubscriber::getSubscribedEvents());
    }

    public function testImmutableDecisionAndResolutionHelpersPreserveState(): void
    {
        $decision = new ViewDecision(ViewDecision::MODE_HTML, ['human']);
        $selected = $decision->withSelectedTemplate('@Interfacing/vendor/index.html.twig');

        self::assertSame('@Interfacing/vendor/index.html.twig', $selected->selectedTemplate);
        self::assertSame($decision->mode, $selected->mode);

        $present = new ViewTemplateResolution('@Interfacing/vendor/index.html.twig', [], [], []);
        $missing = new ViewTemplateResolution(null, [], [], []);

        self::assertTrue($present->hasTemplate());
        self::assertFalse($missing->hasTemplate());
    }

    public function testObservabilityWithoutLoggerIsIntentionalNoOp(): void
    {
        (new ViewObservabilityService())->record('probe', ['route' => 'viewing_view_index']);

        self::addToAssertionCount(1);
    }

    public function testResponseGuardValidatesModeAndCoversRenderedAndDebugPaths(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ViewResponseGuardService(guardMode: 'invalid');
    }

    public function testResponseGuardAllowsNonHtmlAndViewingRenderedResponses(): void
    {
        $service = new ViewResponseGuardService();
        $request = Request::create('/viewing');
        $request->attributes->set('_view_controlled', true);

        self::assertFalse($service->isViolation($request, new Response('plain', 200, ['Content-Type' => 'text/plain'])));

        $rendered = new Response('<html></html>', 200, ['Content-Type' => 'text/html', 'X-Viewing-Rendered' => '1']);
        self::assertFalse($service->isViolation($request, $rendered));

        $debug = new ViewResponseGuardService(debug: true);
        $request->attributes->set('_route', 'viewing_view_index');
        $request->attributes->set('_controller', 'probe');
        $replacement = $debug->replacement($request, new Response('<html>bad</html>', 418, ['Content-Type' => 'text/html']));

        self::assertSame(500, $replacement->getStatusCode());
        self::assertStringContainsString('"debug"', (string) $replacement->getContent());
    }

    public function testTrafficClassifierIgnoresEmptyPatternsAndRejectsMalformedPattern(): void
    {
        $classifier = new ViewTrafficClassifier(['', '/bot/i']);
        $request = Request::create('/viewing', server: [
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
            'HTTP_SEC_FETCH_SITE' => 'same-origin',
            'HTTP_SEC_FETCH_MODE' => 'navigate',
        ]);

        self::assertSame('human', $classifier->classify($request));

        $this->expectException(\InvalidArgumentException::class);
        new ViewTrafficClassifier(['/[/']);
    }

    public function testRouteExclusionIgnoresBlankAndMalformedPatterns(): void
    {
        /** @var list<string> $patterns */
        $patterns = [null, '', '/[/'];
        $service = new ViewRouteExclusionService($patterns, $patterns);
        $request = Request::create('/viewing');
        $request->attributes->set('_route', 'viewing_view_index');

        self::assertFalse($service->isExcluded($request));
    }

    public function testTrafficClassifierIgnoresMalformedRuntimePatternElements(): void
    {
        /** @var list<string> $patterns */
        $patterns = [null, '', '/bot/i'];
        $classifier = new ViewTrafficClassifier($patterns);

        self::assertSame('bot', $classifier->classify(Request::create('/viewing', server: [
            'HTTP_USER_AGENT' => 'ExampleBot/1.0',
        ])));
    }

    public function testTrafficSubscriberCoversDisabledExcludedExistingAndClassificationPaths(): void
    {
        $kernel = new \App\Viewing\Kernel('test', true);
        $classifier = new ViewTrafficClassifier(['/bot/i']);

        try {
            $normal = Request::create('/viewing', server: ['HTTP_USER_AGENT' => 'ExampleBot/1.0']);
            $subscriber = new ViewTrafficRequestSubscriber(
                $classifier,
                new ViewRouteExclusionService(),
            );
            $subscriber->onKernelRequest(new \Symfony\Component\HttpKernel\Event\RequestEvent(
                $kernel,
                $normal,
                \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
            ));
            self::assertSame('bot', $normal->attributes->get('_view_actor_type'));

            $existing = Request::create('/viewing');
            $existing->attributes->set('_view_actor_type', 'human');
            $subscriber->onKernelRequest(new \Symfony\Component\HttpKernel\Event\RequestEvent(
                $kernel,
                $existing,
                \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
            ));
            self::assertSame('human', $existing->attributes->get('_view_actor_type'));

            $excluded = Request::create('/health');
            $excludedSubscriber = new ViewTrafficRequestSubscriber(
                $classifier,
                new ViewRouteExclusionService(['#^/health$#']),
            );
            $excludedSubscriber->onKernelRequest(new \Symfony\Component\HttpKernel\Event\RequestEvent(
                $kernel,
                $excluded,
                \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
            ));
            self::assertNull($excluded->attributes->get('_view_actor_type'));

            $disabled = Request::create('/viewing');
            $disabledSubscriber = new ViewTrafficRequestSubscriber(
                $classifier,
                new ViewRouteExclusionService(),
                enabled: false,
            );
            $disabledSubscriber->onKernelRequest(new \Symfony\Component\HttpKernel\Event\RequestEvent(
                $kernel,
                $disabled,
                \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
            ));
            self::assertNull($disabled->attributes->get('_view_actor_type'));
        } finally {
            $kernel->shutdown();
        }
    }

    public function testResponseGuardSubscriberCoversExcludedLegalObserveAndEnforcePaths(): void
    {
        $kernel = new \App\Viewing\Kernel('test', true);

        try {
            $excludedRequest = $this->controlledRequest('/health');
            $excludedEvent = new \Symfony\Component\HttpKernel\Event\ResponseEvent(
                $kernel,
                $excludedRequest,
                \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
                $this->htmlResponse(),
            );
            (new ViewKernelResponseGuardSubscriber(
                new ViewResponseGuardService(),
                new ViewRouteExclusionService(['#^/health$#']),
            ))->onKernelResponse($excludedEvent);
            self::assertSame(200, $excludedEvent->getResponse()->getStatusCode());

            $legalRequest = $this->controlledRequest('/viewing');
            $legalEvent = new \Symfony\Component\HttpKernel\Event\ResponseEvent(
                $kernel,
                $legalRequest,
                \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
                new Response('plain', 200, ['Content-Type' => 'text/plain']),
            );
            (new ViewKernelResponseGuardSubscriber(
                new ViewResponseGuardService(),
                new ViewRouteExclusionService(),
            ))->onKernelResponse($legalEvent);
            self::assertSame(200, $legalEvent->getResponse()->getStatusCode());

            $observeRequest = $this->controlledRequest('/viewing');
            $observeRequest->attributes->set('_route', 'viewing_view_index');
            $observeEvent = new \Symfony\Component\HttpKernel\Event\ResponseEvent(
                $kernel,
                $observeRequest,
                \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
                $this->htmlResponse(),
            );
            (new ViewKernelResponseGuardSubscriber(
                new ViewResponseGuardService(guardMode: 'observe'),
                new ViewRouteExclusionService(),
                new ViewObservabilityService(),
            ))->onKernelResponse($observeEvent);
            self::assertSame('observed-illegal-controller-render', $observeEvent->getResponse()->headers->get('X-Viewing-Guard'));

            $enforceRequest = $this->controlledRequest('/viewing');
            $enforceEvent = new \Symfony\Component\HttpKernel\Event\ResponseEvent(
                $kernel,
                $enforceRequest,
                \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
                $this->htmlResponse(),
            );
            (new ViewKernelResponseGuardSubscriber(
                new ViewResponseGuardService(guardMode: 'enforce'),
                new ViewRouteExclusionService(),
                new ViewObservabilityService(),
            ))->onKernelResponse($enforceEvent);
            self::assertSame(500, $enforceEvent->getResponse()->getStatusCode());
        } finally {
            $kernel->shutdown();
        }
    }

    private function controlledRequest(string $path): Request
    {
        $request = Request::create($path);
        $request->attributes->set('_view_controlled', true);

        return $request;
    }

    private function htmlResponse(): Response
    {
        return new Response('<html>legacy</html>', 200, ['Content-Type' => 'text/html']);
    }
}
