<?php

declare(strict_types=1);

namespace App\Viewing\Service\View;

use App\Viewing\ServiceInterface\View\ViewTrafficClassifierInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ViewTrafficClassifier implements ViewTrafficClassifierInterface
{
    /**
     * @param list<string> $botUserAgentPatterns
     */
    public function __construct(
        private array $botUserAgentPatterns = [],
    ) {
    }

    public function classify(Request $request): ?string
    {
        $userAgent = (string) $request->headers->get('User-Agent', '');

        if ('' === trim($userAgent)) {
            return 'unknown';
        }

        foreach ($this->botUserAgentPatterns as $pattern) {
            if (!\is_string($pattern) || '' === trim($pattern)) {
                continue;
            }

            $result = @preg_match(trim($pattern), $userAgent);

            if (1 === $result) {
                return 'bot';
            }
        }

        $secFetchSite = (string) $request->headers->get('Sec-Fetch-Site', '');
        $secFetchMode = (string) $request->headers->get('Sec-Fetch-Mode', '');

        if ('' !== $secFetchSite || '' !== $secFetchMode) {
            return 'human';
        }

        return null;
    }
}
