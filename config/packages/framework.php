<?php

declare(strict_types=1);

use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework): void {
    $framework->secret('viewing-standalone-dev-secret-change-me');
    $framework->httpMethodOverride(false);
    $framework->handleAllThrowables(true);

    $framework->phpErrors()
        ->log(true);
};
