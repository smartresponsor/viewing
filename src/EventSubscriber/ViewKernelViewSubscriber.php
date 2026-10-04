<?php

declare(strict_types=1);

namespace App\Viewing\EventSubscriber;

use App\Viewing\ServiceInterface\ViewDecisionServiceInterface;
use App\Viewing\ServiceInterface\ViewJsonResponseFactoryInterface;
use App\Viewing\ServiceInterface\ViewObservabilityServiceInterface;
use App\Viewing\ServiceInterface\ViewPayloadNormalizerInterface;
use App\Viewing\ServiceInterface\ViewRequestContextFactoryInterface;
use App\Viewing\ServiceInterface\ViewTemplateCandidateServiceInterface;
use App\Viewing\ServiceInterface\ViewTemplateRendererInterface;
use App\Viewing\Value\ViewDecision;
use App\Viewing\Value\ViewDecisionReason;
use App\Viewing\Value\ViewRequestContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Coordinates the ViewKernelViewSubscriber Symfony event responsibility inside the Viewing presentation pipeline.
 */
final readonly class ViewKernelViewSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ViewPayloadNormalizerInterface $payloadNormalizer,
        private ViewRequestContextFactoryInterface $contextFactory,
        private ViewDecisionServiceInterface $decisionService,
        private ViewTemplateCandidateServiceInterface $candidateService,
        private ViewTemplateRendererInterface $templateRenderer,
        private ViewJsonResponseFactoryInterface $jsonResponseFactory,
        private bool $enabled = true,
        private ?ViewObservabilityServiceInterface $observability = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 0],
        ];
    }

    /**
     * Transforms neutral controller results into the final HTML or JSON representation.
     */
    public function onKernelView(ViewEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $result = $event->getControllerResult();

        if (!$this->payloadNormalizer->supports($result)) {
            return;
        }

        /**
         * Once a controller explicitly returns a Viewing payload or a surface
         * contract, the payload itself is the opt-in signal. Route exclusions stay
         * on defensive response guarding, not on the kernel.view render boundary.
         */
        $payload = $this->payloadNormalizer->normalize($result);
        $context = $this->contextFactory->create($event->getRequest());
        $decision = $this->decisionService->decide($payload, $context);
        $this->recordDecision($context, $decision);

        if (ViewDecision::MODE_JSON === $decision->mode) {
            $event->setResponse($this->jsonResponseFactory->create($payload, $context, $decision));

            return;
        }

        $templateCandidates = $this->candidateService->candidates($payload, $context);
        $decision = $decision->withTemplateCandidates($templateCandidates);
        $htmlResponse = $this->templateRenderer->render($payload, $context, $decision);

        if (null !== $htmlResponse) {
            $htmlResponse->headers->set('X-Viewing-Rendered', '1');
            $this->recordHtmlResponse($context, $htmlResponse, $templateCandidates);
            $event->setResponse($htmlResponse);

            return;
        }

        $fallbackDecision = $this->fallbackDecision($event, $templateCandidates);

        $this->observability?->record('fallback', [
            'route' => $context->routeName,
            'path' => $context->path,
            'actor_type' => $context->actorType,
            'decision_mode' => ViewDecision::MODE_JSON,
            'reason' => $fallbackDecision->reasons[0] ?? null,
            'status_code' => $fallbackDecision->statusCodeOverride,
        ], null !== $fallbackDecision->statusCodeOverride ? 'error' : 'warning');

        $event->setResponse($this->jsonResponseFactory->create(
            $payload,
            $context,
            $fallbackDecision,
        ));
    }

    /**
     * Records the representation decision without expanding the kernel.view orchestration flow.
     */
    private function recordDecision(ViewRequestContext $context, ViewDecision $decision): void
    {
        $this->observability?->record('decision', [
            'route' => $context->routeName,
            'path' => $context->path,
            'actor_type' => $context->actorType,
            'decision_mode' => $decision->mode,
            'reason' => $decision->reasons[0] ?? null,
        ]);
    }

    /**
     * Records a successful HTML representation without mixing diagnostics into response orchestration.
     *
     * @param list<string> $templateCandidates
     */
    private function recordHtmlResponse(ViewRequestContext $context, Response $response, array $templateCandidates): void
    {
        $this->observability?->record('html_response', [
            'route' => $context->routeName,
            'path' => $context->path,
            'actor_type' => $context->actorType,
            'decision_mode' => ViewDecision::MODE_HTML,
            'status_code' => $response->getStatusCode(),
            'candidate_depth' => \count($templateCandidates),
        ]);
    }

    /**
     * Builds the deterministic JSON fallback decision from template-resolution diagnostics.
     *
     * @param list<string> $templateCandidates
     */
    private function fallbackDecision(ViewEvent $event, array $templateCandidates): ViewDecision
    {
        $fallbackReasons = [] === $templateCandidates
            ? [ViewDecisionReason::TemplateCandidateChainEmpty->value]
            : [ViewDecisionReason::TemplateMissingFallback->value];
        $statusCodeOverride = null;

        $loaderFailures = $event->getRequest()->attributes->get('_view_loader_failures');
        if (\is_array($loaderFailures) && [] !== $loaderFailures) {
            $fallbackReasons[] = ViewDecisionReason::TemplateLoaderFailed->value;
            $statusCodeOverride = 500;
        }

        $renderFailures = $event->getRequest()->attributes->get('_view_render_failures');
        if (\is_array($renderFailures) && [] !== $renderFailures) {
            $firstFailure = $renderFailures[0] ?? [];
            $exception = \is_array($firstFailure) && \is_string($firstFailure['exception'] ?? null)
                ? $firstFailure['exception']
                : 'unknown';
            $fallbackReasons[] = ViewDecisionReason::TemplateRenderFailed->value.':'.$exception;
            $statusCodeOverride = 500;
        }

        return new ViewDecision(
            ViewDecision::MODE_JSON,
            $fallbackReasons,
            $templateCandidates,
            statusCodeOverride: $statusCodeOverride,
        );
    }
}
