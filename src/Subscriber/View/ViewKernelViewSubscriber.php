<?php

declare(strict_types=1);

namespace App\Viewing\Subscriber\View;

use App\Viewing\ServiceInterface\View\ViewDecisionServiceInterface;
use App\Viewing\ServiceInterface\View\ViewJsonResponseFactoryInterface;
use App\Viewing\ServiceInterface\View\ViewPayloadNormalizerInterface;
use App\Viewing\ServiceInterface\View\ViewRequestContextFactoryInterface;
use App\Viewing\ServiceInterface\View\ViewTemplateCandidateServiceInterface;
use App\Viewing\ServiceInterface\View\ViewTemplateRendererInterface;
use App\Viewing\Value\View\ViewDecision;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 0],
        ];
    }

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

        if (ViewDecision::MODE_JSON === $decision->mode) {
            $event->setResponse($this->jsonResponseFactory->create($payload, $context, $decision));

            return;
        }

        $templateCandidates = $this->candidateService->candidates($payload, $context);
        $decision = $decision->withTemplateCandidates($templateCandidates);
        $htmlResponse = $this->templateRenderer->render($payload, $context, $decision);

        if (null !== $htmlResponse) {
            $htmlResponse->headers->set('X-Viewing-Rendered', '1');
            $event->setResponse($htmlResponse);

            return;
        }

        $event->setResponse($this->jsonResponseFactory->create(
            $payload,
            $context,
            new ViewDecision(ViewDecision::MODE_JSON, ['template_missing_json_fallback'], $templateCandidates),
        ));
    }
}
