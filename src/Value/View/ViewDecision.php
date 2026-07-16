<?php

declare(strict_types=1);

namespace App\Viewing\Value\View;

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
     * @param list<string> $templateCandidates
     */
    public function withTemplateCandidates(array $templateCandidates): self
    {
        return new self($this->mode, $this->reasons, $templateCandidates, $this->selectedTemplate, $this->statusCodeOverride);
    }

    public function withSelectedTemplate(?string $selectedTemplate): self
    {
        return new self($this->mode, $this->reasons, $this->templateCandidates, $selectedTemplate, $this->statusCodeOverride);
    }
}
