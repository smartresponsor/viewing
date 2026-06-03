<?php

declare(strict_types=1);

namespace App\Viewing\Value\View;

/**
 * Immutable trace of Viewing template resolution.
 *
 * The selected template is only a rendering target chosen by Viewing. Producer
 * components never regain control and never render local fallback templates
 * themselves.
 */
final readonly class ViewTemplateResolution
{
    /**
     * @param list<array{template: string, exists: bool, error?: string}> $checkedCandidates
     * @param list<string>                                                $availableCandidates
     * @param list<string>                                                $missingCandidates
     */
    public function __construct(
        public ?string $selectedTemplate,
        public array $checkedCandidates,
        public array $availableCandidates,
        public array $missingCandidates,
    ) {
    }

    public function hasTemplate(): bool
    {
        return null !== $this->selectedTemplate;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'selected_template' => $this->selectedTemplate,
            'checked_candidates' => $this->checkedCandidates,
            'available_candidates' => $this->availableCandidates,
            'missing_candidates' => $this->missingCandidates,
        ];
    }
}
