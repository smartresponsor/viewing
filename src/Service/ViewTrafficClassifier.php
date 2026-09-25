<?php

declare(strict_types=1);

namespace App\Viewing\Service;

use App\Viewing\ServiceInterface\ViewTrafficClassifierInterface;
use App\Viewing\Value\ViewActorType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Classifies request traffic from user-agent and Fetch Metadata evidence for representation-policy decisions.
 */
final readonly class ViewTrafficClassifier implements ViewTrafficClassifierInterface
{
    /**
     * @param list<string> $botUserAgentPatterns
     */
    public function __construct(
        private array $botUserAgentPatterns = [],
    ) {
        $patterns = $this->botUserAgentPatterns;
        array_walk($patterns, static function (mixed $pattern): void {
            if (!\is_string($pattern) || '' === trim($pattern)) {
                return;
            }

            if (false === @preg_match(trim($pattern), '')) {
                throw new \InvalidArgumentException(sprintf('Invalid bot user-agent pattern: %s', $pattern));
            }
        });
    }

    /**
     * Classifies request traffic as human, bot, or unknown for representation policy decisions.
     */
    public function classify(Request $request): string
    {
        $userAgent = (string) $request->headers->get('User-Agent', '');

        return match (true) {
            '' === trim($userAgent) => ViewActorType::Unknown->value,
            $this->isBotUserAgent($userAgent) => ViewActorType::Bot->value,
            $this->hasBrowserFetchEvidence($request) => ViewActorType::Human->value,
            default => ViewActorType::Unknown->value,
        };
    }

    private function isBotUserAgent(string $userAgent): bool
    {
        return array_any(
            $this->botUserAgentPatterns,
            static fn (mixed $pattern): bool => \is_string($pattern)
                && '' !== trim($pattern)
                && 1 === preg_match(trim($pattern), $userAgent),
        );
    }

    private function hasBrowserFetchEvidence(Request $request): bool
    {
        $secFetchSite = strtolower((string) $request->headers->get('Sec-Fetch-Site', ''));
        $secFetchMode = strtolower((string) $request->headers->get('Sec-Fetch-Mode', ''));

        return \in_array($secFetchSite, ['same-origin', 'same-site', 'cross-site', 'none'], true)
            && \in_array($secFetchMode, ['navigate', 'same-origin', 'cors', 'no-cors'], true);
    }
}
