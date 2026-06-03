<?php

declare(strict_types=1);

use Symfony\Config\ViewingConfig;

return static function (ViewingConfig $viewing): void {
    $viewing->enabled(true);
    $viewing->localComponentFallbackEnabled(true);
    $viewing->responseGuardEnabled(true);
    $viewing->diagnosticMode('safe');
    $viewing->trafficClassifierEnabled(true);

    $viewing->excludedPathPatterns([
        '#^/_wdt(?:/|$)#',
        '#^/_profiler(?:/|$)#',
        '#^/assets(?:/|$)#',
        '#^/build(?:/|$)#',
        '#^/favicon\\.ico$#',
        '#^/robots\\.txt$#',
        '#^/sitemap\\.xml$#',
        '#^/health$#',
        '#^/metrics$#',
    ]);
};
