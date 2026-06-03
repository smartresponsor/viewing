<?php

declare(strict_types=1);

namespace App\Viewing\Service\View;

use App\Viewing\ServiceInterface\View\ViewTemplateResolverInterface;
use App\Viewing\Value\View\ViewTemplateResolution;
use Twig\Environment;

final readonly class ViewTemplateResolver implements ViewTemplateResolverInterface
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    /**
     * @param list<string> $templateCandidates
     */
    public function resolve(array $templateCandidates): ViewTemplateResolution
    {
        $checked = [];
        $available = [];
        $missing = [];

        foreach (array_values(array_unique($templateCandidates)) as $candidate) {
            if (!\is_string($candidate) || '' === trim($candidate)) {
                continue;
            }

            $template = trim($candidate);

            try {
                $exists = $this->twig->getLoader()->exists($template);
            } catch (\Throwable $exception) {
                $checked[] = [
                    'template' => $template,
                    'exists' => false,
                    'error' => $exception::class,
                ];
                $missing[] = $template;
                continue;
            }

            $checked[] = [
                'template' => $template,
                'exists' => $exists,
            ];

            if ($exists) {
                $available[] = $template;
                continue;
            }

            $missing[] = $template;
        }

        return new ViewTemplateResolution(
            selectedTemplate: $available[0] ?? null,
            checkedCandidates: $checked,
            availableCandidates: $available,
            missingCandidates: $missing,
        );
    }
}
