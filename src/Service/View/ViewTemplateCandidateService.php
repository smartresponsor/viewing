<?php

declare(strict_types=1);

namespace App\Viewing\Service\View;

use App\Viewing\ServiceInterface\View\ViewTemplateCandidateServiceInterface;
use App\Viewing\Value\View\ViewPayload;
use App\Viewing\Value\View\ViewRequestContext;

final readonly class ViewTemplateCandidateService implements ViewTemplateCandidateServiceInterface
{
    public function __construct(
        private string $interfacingTwigNamespace = 'Interfacing',
        private string $viewingTwigNamespace = 'Viewing',
        private bool $localComponentFallbackEnabled = true,
        private string $diagnosticMode = 'safe',
    ) {
    }

    public function candidates(ViewPayload $payload, ViewRequestContext $context): array
    {
        $resource = $this->slug($payload->surface);
        $operation = $this->slug($payload->operation);
        $candidates = [];

        // Interfacing owns visible surface templates. Resolve the concrete
        // operation first, then fall back to the surface index.
        // Filesystem: Interfacing/templates/<resource>/<operation>.html.twig
        $candidates[] = sprintf('@%s/%s/%s.html.twig', $this->interfacingTwigNamespace, $resource, $operation);

        // An explicit producer template is a more specific fallback than the
        // generic resource index, but remains below an Interfacing-owned exact
        // operation template.
        $explicitTemplate = $this->explicitComponentTemplate($payload);
        if (null !== $explicitTemplate) {
            $candidates[] = $explicitTemplate;
        }

        // Filesystem: Interfacing/templates/<resource>/index.html.twig
        $candidates[] = sprintf('@%s/%s/index.html.twig', $this->interfacingTwigNamespace, $resource);

        // Filesystem: Interfacing/templates/index.html.twig
        $candidates[] = sprintf('@%s/index.html.twig', $this->interfacingTwigNamespace);

        foreach ($this->localComponentCandidates($payload) as $candidate) {
            $candidates[] = $candidate;
        }

        // Viewing-owned diagnostic fallback. This is not a producer template and
        // does not return rendering control back to a producer controller.
        if ('off' !== $this->diagnosticMode) {
            $candidates[] = sprintf('@%s/view/index.html.twig', $this->viewingTwigNamespace);
        }

        return array_values(array_unique($candidates));
    }

    private function explicitComponentTemplate(ViewPayload $payload): ?string
    {
        $template = $payload->data['templateName'] ?? null;
        if (!\is_string($template)) {
            return null;
        }

        $template = trim($template);
        if ('' === $template || str_contains($template, '..') || str_contains($template, "\0")) {
            return null;
        }

        return $template;
    }

    /**
     * Local component fallback is intentionally below Interfacing. The producer
     * component does not render it; Viewing renders it centrally only after the
     * Interfacing resource index and Interfacing root index are unavailable.
     *
     * Filesystem convention inside the producer/component package:
     * <component>/templates/index.html.twig
     *
     * @return list<string>
     */
    private function localComponentCandidates(ViewPayload $payload): array
    {
        if (!$this->localComponentFallbackEnabled) {
            return [];
        }

        if (null === $payload->component || '' === trim($payload->component)) {
            return [];
        }

        return [sprintf('@%s/index.html.twig', $this->twigNamespace($payload->component))];
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? $value;
        $value = trim($value, '-');

        return '' !== $value ? $value : 'index';
    }

    private function twigNamespace(string $component): string
    {
        $component = preg_replace('/[^A-Za-z0-9_]+/', '', $component) ?? $component;

        return '' !== $component ? ucfirst($component) : 'Component';
    }
}
