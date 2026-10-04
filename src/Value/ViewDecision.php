<?php

declare(strict_types=1);

namespace App\Viewing\Value;

/**
 * Represents the Decision immutable value used by the Viewing presentation decision pipeline.
 */
final readonly class ViewDecision
{
    public const MODE_HTML = 'html';
    public const MODE_JSON = 'json';
    public const MODE_PASS = 'pass';

    /**
     * @param list<string> $reasons
     * @param list<string> $templateCandidates
     */
    public function __construct(
        public string $mode,
        public array $reasons = [],
        public array $templateCandidates = [],
        public ?string $selectedTemplate = null,
        public ?int $statusCodeOverride = null,
    ) {
    }

    /**
     * Returns a new immutable decision carrying the evaluated template candidate chain.
     *
     * @param list<string> $templateCandidates
     */
    public function withTemplateCandidates(array $templateCandidates): self
    {
        return new self($this->mode, $this->reasons, $templateCandidates, $this->selectedTemplate, $this->statusCodeOverride);
    }

    /**
     * Returns a new immutable decision carrying the template selected for HTML rendering.
     */
    public function withSelectedTemplate(?string $selectedTemplate): self
    {
        return new self($this->mode, $this->reasons, $this->templateCandidates, $selectedTemplate, $this->statusCodeOverride);
    }
}
