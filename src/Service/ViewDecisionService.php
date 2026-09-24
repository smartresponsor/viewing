<?php

declare(strict_types=1);

namespace App\Viewing\Service;

use App\Viewing\ServiceInterface\ViewDecisionServiceInterface;
use App\Viewing\Value\ViewActorType;
use App\Viewing\Value\ViewDecision;
use App\Viewing\Value\ViewDecisionReason;
use App\Viewing\Value\ViewPayload;
use App\Viewing\Value\ViewRequestContext;

final readonly class ViewDecisionService implements ViewDecisionServiceInterface
{
    /**
     * @param list<string> $botActorValues
     */
    public function __construct(
        private array $botActorValues = ['bot'],
        private string $unknownActorPolicy = ViewDecision::MODE_HTML,
    ) {
        if (!\in_array($this->unknownActorPolicy, [ViewDecision::MODE_HTML, ViewDecision::MODE_JSON], true)) {
            throw new \InvalidArgumentException('Unknown actor policy must be html or json.');
        }
    }

    public function decide(ViewPayload $payload, ViewRequestContext $context): ViewDecision
    {
        $payloadFormat = strtolower($payload->format);
        $actorType = null !== $context->actorType ? strtolower($context->actorType) : null;

        if (null !== $actorType && \in_array($actorType, $this->normalizedBotActorValues(), true)) {
            return new ViewDecision(ViewDecision::MODE_JSON, [ViewDecisionReason::ActorTypeForcesJson->value]);
        }

        if ('json' === strtolower($context->requestFormat)) {
            return new ViewDecision(ViewDecision::MODE_JSON, [ViewDecisionReason::RequestFormatJson->value]);
        }

        if ('json' === $payloadFormat) {
            return new ViewDecision(ViewDecision::MODE_JSON, [ViewDecisionReason::PayloadFormatJson->value]);
        }

        if (
            true === ($context->routeAttributes['_view_controlled'] ?? false)
            && 'html' === strtolower($context->requestFormat)
        ) {
            return new ViewDecision(ViewDecision::MODE_HTML, [ViewDecisionReason::ControlledHtmlRoute->value]);
        }

        if ($context->prefersJson && !$context->prefersHtml) {
            return new ViewDecision(ViewDecision::MODE_JSON, [ViewDecisionReason::AcceptPrefersJson->value]);
        }

        if ($context->xmlHttpRequest && !$context->prefersHtml) {
            return new ViewDecision(ViewDecision::MODE_JSON, [ViewDecisionReason::XmlHttpRequestWithoutHtml->value]);
        }

        if (ViewActorType::Unknown->value === $actorType && ViewDecision::MODE_JSON === $this->unknownActorPolicy) {
            return new ViewDecision(ViewDecision::MODE_JSON, [ViewDecisionReason::UnknownActorForcesJson->value]);
        }

        return new ViewDecision(ViewDecision::MODE_HTML, [ViewDecisionReason::HtmlCandidateAllowed->value]);
    }

    /**
     * @return list<string>
     */
    private function normalizedBotActorValues(): array
    {
        return array_values(array_unique(array_map(
            static fn (string $value): string => strtolower($value),
            array_filter($this->botActorValues, static fn (mixed $value): bool => \is_string($value) && '' !== trim($value)),
        )));
    }
}
