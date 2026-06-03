<?php

declare(strict_types=1);

namespace App\Viewing\Service\View;

use App\Viewing\ServiceInterface\View\ViewDecisionServiceInterface;
use App\Viewing\Value\View\ViewDecision;
use App\Viewing\Value\View\ViewPayload;
use App\Viewing\Value\View\ViewRequestContext;

final readonly class ViewDecisionService implements ViewDecisionServiceInterface
{
    /**
     * @param list<string> $botActorValues
     */
    public function __construct(
        private array $botActorValues = ['bot'],
    ) {
    }

    public function decide(ViewPayload $payload, ViewRequestContext $context): ViewDecision
    {
        $reasons = [];
        $payloadFormat = strtolower($payload->format);
        $actorType = null !== $context->actorType ? strtolower($context->actorType) : null;

        if (null !== $actorType && \in_array($actorType, $this->normalizedBotActorValues(), true)) {
            return new ViewDecision(ViewDecision::MODE_JSON, ['actor_type_forces_json']);
        }

        if ('json' === strtolower($context->requestFormat)) {
            return new ViewDecision(ViewDecision::MODE_JSON, ['request_format_json']);
        }

        if ('json' === $payloadFormat) {
            return new ViewDecision(ViewDecision::MODE_JSON, ['payload_format_json']);
        }

        if ($context->prefersJson && !$context->prefersHtml) {
            return new ViewDecision(ViewDecision::MODE_JSON, ['accept_header_prefers_json']);
        }

        if ($context->xmlHttpRequest && !$context->prefersHtml) {
            return new ViewDecision(ViewDecision::MODE_JSON, ['xml_http_request_without_html_preference']);
        }

        $reasons[] = 'html_candidate_allowed';

        return new ViewDecision(ViewDecision::MODE_HTML, $reasons);
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
