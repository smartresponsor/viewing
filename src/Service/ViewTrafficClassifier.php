<?php

declare(strict_types=1);

namespace App\Viewing\Service;

use App\Viewing\ServiceInterface\ViewTrafficClassifierInterface;
use App\Viewing\Value\ViewActorType;
use Symfony\Component\HttpFoundation\Request;

final readonly class ViewTrafficClassifier implements ViewTrafficClassifierInterface
{
    /**
     * @param list<string> $botUserAgentPatterns
     */
    public function __construct(
        private array $botUserAgentPatterns = [],
    ) {
        foreach ($this->botUserAgentPatterns as $pattern) {
            if (!\is_string($pattern) || '' === trim($pattern)) {
                continue;
            }

            if (false === @preg_match(trim($pattern), '')) {
                throw new \InvalidArgumentException(sprintf('Invalid bot user-agent pattern: %s', $pattern));
            }
        }
    }

    public function classify(Request $request): string
    {
        $userAgent = (string) $request->headers->get('User-Agent', '');

        if ('' === trim($userAgent)) {
            return ViewActorType::Unknown->value;
        }

        foreach ($this->botUserAgentPatterns as $pattern) {
            if (!\is_string($pattern) || '' === trim($pattern)) {
                continue;
            }

            if (1 === preg_match(trim($pattern), $userAgent)) {
                return ViewActorType::Bot->value;
            }
        }

        $secFetchSite = strtolower((string) $request->headers->get('Sec-Fetch-Site', ''));
        $secFetchMode = strtolower((string) $request->headers->get('Sec-Fetch-Mode', ''));

        if (
            \in_array($secFetchSite, ['same-origin', 'same-site', 'cross-site', 'none'], true)
            && \in_array($secFetchMode, ['navigate', 'same-origin', 'cors', 'no-cors'], true)
        ) {
            return ViewActorType::Human->value;
        }

        return ViewActorType::Unknown->value;
    }
}
